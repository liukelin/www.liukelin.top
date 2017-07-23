<?php
set_time_limit(5);
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
/**
 * 基于a-start的最短距离计算demo
 * A*寻路
 * liukelin
 * 2017.7.23 
 */


// 接受参数
$map_width = (int)$_REQUEST['map_width']; 
$map_height = (int)$_REQUEST['map_height']; 
$location_hindrance = $_REQUEST['location_hindrance']; // 障碍物坐标  |x-y|x-y
$location_begin = $_REQUEST['location_begin']; // 起点物坐标 x-y
$location_end = $_REQUEST['location_end']; // 终点坐标  x-y
if (!$location_hindrance || !$location_begin || !$location_end) {
    exit(json_encode(array('c'=>-1,'msg'=>'参数错误')));
}

$location_begin = explode('-', $location_begin);
$location_end = explode('-', $location_end);

// 地图大小
$map_width = $map_width;  // x
$map_height = $map_height; // y

// 是否允许障碍物边界斜向通过 
$is_agree = 0;

// 消耗 
$cost_1 = 10; //左右消耗值 
$cost_2 = 14; //对角消耗值

// 设置起始和结束坐标 
$begin_x = $location_begin[0]; 
$begin_y = $location_begin[1]; 
$end_x = $location_end[0]; 
$end_y = $location_end[1];

// 设置障碍物坐标 
// $hindrance = array(); 
// $hindrance[] = array(1,2); 
// $hindrance[] = array(1,3); 
// $hindrance[] = array(1,1); 
$location_hindrance = array_filter(explode('|', $location_hindrance));
foreach ($location_hindrance as $key => $val) {
    $hindrance[$key] = explode('-', $val);
}


// 生成地图
$area = createMap($map_width, $map_height, $begin_x, $begin_y, $end_x, $end_y, $hindrance);

// 初始化 
$open_arr = array(); // 开启坐标集合
$close_arr = array(); // 关闭坐标集合
$path = array(); // 路径坐标集合

// 把起始格添加到开启列表 
$open_arr[0]['x'] = $begin_x; 
$open_arr[0]['y'] = $begin_y; 
$open_arr[0]['G'] = 0;      // G = 从起点A，沿着产生的路径，移动到网格上指定方格的移动耗费。
$open_arr[0]['H'] = getH($begin_x,$begin_y,$end_x,$end_y);  // H = 从网格上那个方格移动到终点B的预估移动耗费。
$open_arr[0]['F'] = $open_arr[0]['H'];  // F = G + H
$open_arr[0]['p_node'] = array($begin_x, $begin_y);

// 循环 
while(1) {

    // 取得最小F值的格子作为当前格 
    $cur_node = getLowestFNode($open_arr);

    // 从开启列表中删除此格子 
    $open_arr = removeNode($open_arr, $cur_node['x'], $cur_node['y']);

    // 将当前点加入到关闭列表 
    $close_arr[] = $cur_node;
    
    //取周边节点
    $round_list = getRoundNode($cur_node['x'], $cur_node['y']); 
    $round_num = count($round_list);
    // var_dump($round_list);die();
    
    for($i=0; $i<$round_num; $i++) {
        //所有周边节点中第i和节点的x,y
        $pos_arr = $round_list[$i];

        // 跳过已在关闭列表中的格子，障碍格子和 夹角转角格子 
        if( isOutMap($pos_arr[0], $pos_arr[1], $map_width, $map_height) 
            || isNodeClose($pos_arr[0], $pos_arr[1]) 
            || isHindrance($pos_arr[0], $pos_arr[1]) 
            || isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])
        ){ 
            continue; 
        }

        $new_g = getG($pos_arr[0],$pos_arr[1],$cur_node['x'],$cur_node['y']); 
        $total_g = $new_g + $cur_node['G'];

        // 如果节点已在开启列表中，重新计算一下G值 ，否则返回false
        $rs_open = isNodeOpen($pos_arr[0], $pos_arr[1]); 
        if(!$rs_open) { 

            //不在opne列表
            $arr[$i] = array(); 
            $arr[$i]['x'] = $pos_arr[0]; 
            $arr[$i]['y'] = $pos_arr[1]; 
            $arr[$i]['G'] = $total_g; 
            $arr[$i]['H'] = getH($pos_arr[0], $pos_arr[1], $end_x, $end_y); 
            $arr[$i]['F'] = $arr[$i]['G'] + $arr[$i]['H']; 
            $arr[$i]['p_node']['x'] = $cur_node['x']; 
            $arr[$i]['p_node']['y'] = $cur_node['y']; 
            $open_arr[] = $arr[$i]; 
        
        } else { 
            //在opne列表 G值重估
            $k = $rs_open['index']; 
            if($total_g < $open_arr[$k]['G']) {

                $open_arr[$k]['G'] = $open_arr[$k]['G']; 
                $open_arr[$k]['F'] = $total_g + $open_arr[$k]['H']; 
                $open_arr[$k]['p_node']['x'] = $cur_node['x']; 
                $open_arr[$k]['p_node']['y'] = $cur_node['y']; 
            
            } else { 
                $total_g = $open_arr[$k]['G']; 
            } 
        } 
    }

    if($cur_node['x'] == $end_x && $cur_node['y'] == $end_y) {

        $path = getPath($close_arr); 
        if(!empty($path)) { 

            break; 
        } 
    }

    if(empty($open_arr)) { 
        break; 
    } 
}


// 直接输出
// draw_maps($area, $path);

//返回json
exit(json_encode(array('c'=>0,'path'=>$path)));

/**
 * 回溯路径 
 * @param  [type] $close_arr 关闭坐标集合
 * @return [type]            路径坐标集合
 * Array
    (
        [0] => Array
            (
                [x] => 0
                [y] => 1
            )
        ...
    )
 */
function getPath($close_arr) { 
    
    global $begin_x, $begin_y; 
    $path = array(); 
    $p = $close_arr[count($close_arr)-1]['p_node']; 
    $path[] = $p; 
    while(1) { 

        for($i=0; $i<count($close_arr); $i++) { 

            if($close_arr[$i]['x']==$p['x'] && $close_arr[$i]['y']==$p['y']) { 
                $p=$close_arr[$i]['p_node']; 
                $path[] = $p; 
            } 
        }

        if($p['x']==$begin_x && $p['y']==$begin_y) { 
            break; 
        } 
    }

    return $path; 
}

/** 
 *  
 * [createMap 创建地图]
 * @param  [type] $width     地图宽
 * @param  [type] $height    地图高
 * @param  [type] $begin_x   开始横坐标
 * @param  [type] $begin_y   开始纵坐标
 * @param  [type] $end_x     目的地横坐标
 * @param  [type] $end_y     目的地纵坐标
 * @param  [type] $hindrance 障碍物坐标集合
 * @return [type] 地图坐标 X Y status 0 可通过 -1 障碍        
 * array(
 *     [0]=> array(
 *         [0] => array("x" => 0 , "y" => 0 , "status" => 0),
 *         ... 
 *     ),
 *     [1] => array(
 *         [0] => array("x" => 1 , "y" => 0 , "status" => -1),
 *         ...
 *     ),
 *     ...
 * }
 * 
 */
function createMap($width, $height, $begin_x, $begin_y, $end_x, $end_y, $hindrance) {

    $map = array(); 
    for($i=0; $i<$height; $i++) {

        for($j=0; $j<$width; $j++) {

            $map[$j][$i]['x'] = $j; 
            $map[$j][$i]['y'] = $i;

            $map[$j][$i]['status'] = 0;

            // 设置障碍物 
            if (isInHindrance($hindrance, $j, $i)) { 
                $map[$j][$i]['status'] = -1; 
            }

            // 设置起点 
            if ($j==$begin_x && $i==$begin_y) { 
                $map[$j][$i]['status'] = 1; 
            }

            // 设置终点 
            if ($j==$end_x && $i==$end_y) { 
                $map[$j][$i]['status'] = 9; 
            } 
        } 
    }

    return $map; 
}

/**
 * [getRoundNode 取坐标周边节点 （包括斜向周边）]
 * @param  [type] $x [X坐标]
 * @param  [type] $y [y坐标]
 * @return [type]    [description]
 */
function getRoundNode($x, $y) { 
    $round_arr = array(); 
    $round_arr[0] = array($x-1,$y-1); 
    $round_arr[1] = array($x-1,$y); 
    $round_arr[2] = array($x-1,$y+1); 
    $round_arr[3] = array($x,$y-1); 
    $round_arr[4] = array($x,$y+1); 
    $round_arr[5] = array($x+1,$y-1); 
    $round_arr[6] = array($x+1,$y); 
    $round_arr[7] = array($x+1,$y+1);

    return $round_arr; 
}


/** 
* 判断是否超出地图 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isOutMap($x, $y, $map_width, $map_height) { 
    if($x < 0 || $y < 0 || $x>($map_width - 1) || $y > ($map_height - 1)) { 
        return true; 
    } 
    return false; 
}

/** 
* 判断是否是转角点 
*  isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])) 
* @param 所有周边节点中第i和节点的x,y , 当前节点的 cur_x,cur_y
*/ 
function isCorner($x, $y, $cur_x, $cur_y) { 
    if($x > $cur_x) { 
        if($y > $cur_y) { 
            if(isHindrance($x - 1, $y) || isHindrance($x, $y - 1)) { 
                return true; 
            } 
        } elseif($y < $cur_y) { 
            if(isHindrance($x - 1, $y) || isHindrance($x, $y + 1)) { 
                return true; 
            } 
        } 
    }

    if($x < $cur_x) { 
        if($y < $cur_y) { 
            if(isHindrance($x + 1, $y) || isHindrance($x, $y + 1)) { 
                return true; 
            } 
        } 
        elseif($y > $cur_y) { 
            if(isHindrance($x + 1, $y) || isHindrance($x, $y - 1)) { 
                return true; 
            } 
        } 
    }

    return false; 
}

/** 
* 设置障碍 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isInHindrance($arr, $x, $y) { 
    foreach($arr as $key=>$val) { 
        if($val[0]==$x && $val[1]==$y) { 
            return true; 
        } 
    } 
    return false; 
}

/** 
* 删除节点 
* 
* @param (类型) (参数名) (描述) 
*/ 
function removeNode($arr, $x, $y, $status='') { 
    foreach($arr as $key=>$val) { 
        if(isset($val['x']) && $val['x']==$x && isset($val['y']) && $val['y']==$y) { 
            unset($arr[$key]); 
        } 
    }

    return $arr; 
}

/** 
* 计算G值 
*  F = G + H
*  G = 从起点A，沿着产生的路径，移动到网格上指定方格的移动耗费。
* @param (类型) (参数名) (描述) 
*/ 
function getG($begin_x, $begin_y, $parent_x, $parent_y) { 
    global $cost_1,$cost_2; 
    if(($begin_x - $parent_x) * ($begin_y - $parent_y) != 0) { 
        return $cost_2; 
    } else { 
        return $cost_1; 
    } 
}

/** 
* 计算H值   H = 从网格上那个方格移动到终点B的预估移动耗费。
* F = G + H
* @param (类型) (参数名) (描述) 
*/ 
function getH($begin_x, $begin_y, $end_x, $end_y, $cost=10) { 
    $h_cost = abs(($end_x - $begin_x)*$cost); 
    $v_cost = abs(($end_y - $begin_y)*$cost);
    $c=$h_cost+$v_cost;
    // echo "$begin_x, $begin_y, $end_x, $end_y, $cost^^^";
    // echo $h_cost.'---'.$v_cost.'-'.$c.'>>>';
    // die();
    return $h_cost+$v_cost; 
}

/** 
* 对开启列表排序 
* 
* @param (类型) (参数名) (描述) 
*/ 
function sortOpenList($a, $b) {

    if ($a['F'] == $b['F']) return 0; 
    return ($a['F'] > $b['F']) ? -1 : +1; 
}

/** 
* 取得最小F值的点 
* 
* @param (类型) (参数名) (描述) 
*/ 
function getLowestFNode($open_arr) { 
    usort($open_arr, "sortOpenList"); 
    $node = array(); 
    $i = 0; 
    foreach($open_arr as $key=>$val) { 

        if($i == 0) {
            $node = $val; 
        } else { 
            if($val['F'] <= $node['F']) { 
                $node = $val; 
            } 
        } 
        $i++; 
    }

    return $node; 
}

/** 
* 判断节点是否已关闭 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isNodeClose($node_x, $node_y) { 
    global $close_arr; 
    foreach($close_arr as $key=>$val) {
        if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) { 
            return true; 
        } 
    } 
    return false; 
}

/** 
* 判断节点是否已在开启列表中 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isNodeOpen($node_x, $node_y) { 
    global $open_arr; 
    foreach($open_arr as $key=>$val) {

        if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) {
            $rs['index'] = $key;
            return $rs; 
        } 
    } 
    return false; 
}

/** 
* 判断结点是否是障碍物 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isHindrance($node_x, $node_y) { 
    global $area; 
    if(isset($area[$node_x][$node_y]['status']) && $area[$node_x][$node_y]['status']==-1) { 
        return true; 
    }
    return false; 
}

/** 
* 检查某结点是否在寻路路径中 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isInPath($parent_arr, $x, $y) { 
    foreach($parent_arr as $key=>$val) {

        if(isset($val['x']) && $val['x'] == $x && isset($val['y']) && $val['y'] == $y) { 
            return true; 
        } 
    } 
    return false; 
} 


// print_r($area);
// print_r($path);
// 输出地图
function draw_maps($area, $path){
    foreach ($area as $key => $value) {

        echo '<div style="width:1600px; height:30px;">';
        
        foreach ($area[$key] as $akey => $avalue) {
            
            // 默认地图坐标颜色
            $bgcolor = 'background-color: #cdd;';
            
            //障碍物颜色
            if ($avalue['status']=='-1') {
                $bgcolor = 'background-color: #cad;';
            }

            //轨迹高亮
            foreach ($path as $pkey => $pvalue) {

                if ($pvalue['x']==$avalue['x'] && $pvalue['y']==$avalue['y']) {
                    $bgcolor = ' background-color: green; ';
                }
            }

            echo '<span style="width:80px; height:30px; '.$bgcolor .'line-height:30px; display: block; float:left; padding-right: 0px;">'.
            ($avalue['x']).'-'.$avalue['y'].'-('.$avalue['status'].')  </span>';
        }
        echo '</div>';
        echo '<br>';
    }
    // print_r($path);//$path里存放的就是寻路的结果路径
}

