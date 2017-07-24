<?php

set_time_limit(5);

// A*寻路

$map_width = 13; 
$map_height = 13;

// 是否允许障碍物边界斜向通过 
$is_agree = 1;

// 消耗 
$cost_1 = 10; //左右消耗值 
$cost_2 = 14; //对角消耗值

// 设置起始和结束坐标 
$begin_x = 12; 
$begin_y = 1; 
$end_x = 0; 
$end_y = 0;

// 设置障碍物坐标 
$hindrance = array(); 
$hindrance[] = array(1,2); 
$hindrance[] = array(1,3); 
$hindrance[] = array(1,1); 
// $hindrance[] = array(1,4); 

$hindrance[] = array(1,0); 
$hindrance[] = array(0,6); 
$hindrance[] = array(4,1); 
$hindrance[] = array(5,1); 
$hindrance[] = array(2,4);

$hindrance[] = array(3,1); 
$hindrance[] = array(3,4);
$hindrance[] = array(3,2); 
$hindrance[] = array(3,5); 
// $hindrance[] = array(3,6); 
$hindrance[] = array(3,7); 

$hindrance[] = array(4,4);
$hindrance[] = array(5,4); 
$hindrance[] = array(6,4); 
$hindrance[] = array(6,1); 
$hindrance[] = array(6,2); 
$hindrance[] = array(6,3); 
$hindrance[] = array(5,8); 
$hindrance[] = array(7,4); 
$hindrance[] = array(7,8); 
$hindrance[] = array(6,5); 
$hindrance[] = array(6,6); 
$hindrance[] = array(1,6); 
$hindrance[] = array(8,6);
$hindrance[] = array(10,3);
$hindrance[] = array(10,5);

$area = createMap($map_width, $map_height, $begin_x, $begin_y, $end_x, $end_y, $hindrance);

// 初始化 
$open_arr = array(); 
$close_arr = array(); 
$path = array();

// 把起始格添加到开启列表 
$open_arr[0]['x'] = $begin_x; 
$open_arr[0]['y'] = $begin_y; 
$open_arr[0]['G'] = 0; 
$open_arr[0]['H'] = getH($begin_x,$begin_y,$end_x,$end_y); 
$open_arr[0]['F'] = $open_arr[0]['H']; 
$open_arr[0]['p_node'] = array($begin_x, $begin_y);

// 循环 
while(1) 
{ 
    // 取得最小F值的格子作为当前格 
    $cur_node = getLowestFNode($open_arr);

    // 从开启列表中删除此格子 
    $open_arr = removeNode($open_arr, $cur_node['x'], $cur_node['y']);

    // 将当前点加入到关闭列表 
    $close_arr[] = $cur_node;
    //取周边节点
    $round_list = getRoundNode($cur_node['x'], $cur_node['y'], $is_agree); 
    $round_num = count($round_list);
// var_dump($round_list);die();
    for($i=0; $i<$round_num; $i++) 
    {
        //所有周边节点中第i和节点的x,y
        $pos_arr = $round_list[$i];

        // 跳过已在关闭列表中的格子，障碍格子和 夹角转角格子 
        if( isOutMap($pos_arr[0], $pos_arr[1], $map_width, $map_height) 
        || isNodeClose($pos_arr[0], $pos_arr[1]) 
        || isHindrance($pos_arr[0], $pos_arr[1]) 
        || isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])
        ) 
        { 
            continue; 
        }

        $new_g = getG($pos_arr[0],$pos_arr[1],$cur_node['x'],$cur_node['y']); 
        $total_g = $new_g + $cur_node['G'];

        // 如果节点已在开启列表中，重新计算一下G值 ，否则返回false
        $rs_open = isNodeOpen($pos_arr[0], $pos_arr[1]); 
        if(!$rs_open) 
        { 
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
        } 
        else 
        { 
            //在opne列表 G值重估
            $k = $rs_open['index']; 
            if($total_g < $open_arr[$k]['G']) 
            { 
                $open_arr[$k]['G'] = $open_arr[$k]['G']; 
                $open_arr[$k]['F'] = $total_g + $open_arr[$k]['H']; 
                $open_arr[$k]['p_node']['x'] = $cur_node['x']; 
                $open_arr[$k]['p_node']['y'] = $cur_node['y']; 
            } 
            else 
            { 
                $total_g = $open_arr[$k]['G']; 
            } 
        } 
    }

    if($cur_node['x'] == $end_x && $cur_node['y'] == $end_y) 
    { 
        $path = getPath($close_arr); 
        if(!empty($path)) 
        { 
            break; 
        } 
    }

    if(empty($open_arr)) 
    { 
        break; 
    } 
}

foreach ($area as $key => $value) 
{
    echo '<div style="width:1600px; height:30px;">';
    foreach ($area[$key] as $akey => $avalue) 
    {
        $bgcolor = 'background-color: #cdd;';
        //路径高亮
        if ($avalue['status']=='-1') {
            $bgcolor = 'background-color: #cad;';
        }
        //轨迹高亮
        foreach ($path as $pkey => $pvalue) 
        {

            if ($pvalue['x']==$avalue['x'] && $pvalue['y']==$avalue['y']) 
            {
                $bgcolor = ' background:url(./c.jpg) no-repeat; ';
            }
        }

        echo '<span style="width:80px; height:30px; '.$bgcolor .'line-height:30px; display: block; float:left; padding-right: 0px;">'.
        ($avalue['x']).'-'.$avalue['y'].'-('.$avalue['status'].')  </span>';
    }
    echo '</div>';
    echo '<br>';
}
// print_r($path);//$path里存放的就是寻路的结果路径

/** 
* 回溯路径 
* 
* @param (类型) (参数名) (描述) 
*/ 
function getPath($close_arr) 
{ 
    global $begin_x, $begin_y; 
    $path = array(); 
    $p = $close_arr[count($close_arr)-1]['p_node']; 
    $path[] = $p; 
    while(1) 
    { 
        for($i=0; $i<count($close_arr); $i++) 
        { 
            if($close_arr[$i]['x']==$p['x'] && $close_arr[$i]['y']==$p['y']) 
            { 
                $p=$close_arr[$i]['p_node']; 
                $path[] = $p; 
            } 
        }

        if($p['x']==$begin_x && $p['y']==$begin_y) 
        { 
            break; 
        } 
    }

    return $path; 
}

/** 
* 创建地图 
* 
* @param (类型) (参数名) (描述) 
*/ 
function createMap($width, $height, $begin_x, $begin_y, $end_x, $end_y, $hindrance) 
{

    $map = array(); 
    for($i=0; $i<$height; $i++) 
    { 
        for($j=0; $j<$width; $j++) 
        { 
            $map[$j][$i]['x'] = $j; 
            $map[$j][$i]['y'] = $i;

            $map[$j][$i]['status'] = 0;

            // 设置障碍物 
            if(isInHindrance($hindrance, $j, $i)) 
            { 
                $map[$j][$i]['status'] = -1; 
            }

            // 设置起点 
            if($j==$begin_x && $i==$begin_y) 
            { 
                $map[$j][$i]['status'] = 1; 
            }

            // 设置终点 
            if($j==$end_x && $i==$end_y) 
            { 
                $map[$j][$i]['status'] = 9; 
            } 
        } 
    }

    return $map; 
}

/** 
* 取周边节点 
* 
* @param (类型) (参数名) (描述) 
*/ 
function getRoundNode($x, $y, $is_agree=1) 
{ 
    $round_arr = array(
        array($x-1,$y),    //左
        array($x,$y-1),    //下
        array($x,$y+1),    //上
        array($x+1,$y),    //右
    );

    if ($is_agree==1) {
        $round_arr[] = array($x-1,$y-1);  //左下
        $round_arr[] = array($x-1,$y+1);  //左上
        $round_arr[] = array($x+1,$y-1);  //右下
        $round_arr[] = array($x+1,$y+1);  //右上
    }

    return $round_arr; 
}


/** 
* 判断是否超出地图 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isOutMap($x, $y, $map_width, $map_height) 
{ 
    if($x < 0 || $y < 0 || $x>($map_width - 1) || $y > ($map_height - 1)) 
    { 
        return true; 
    } 
    return false; 
}

/** 
* 判断是否是转角点 
*  isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])) 
* @param 所有周边节点中第i和节点的x,y , 当前节点的 cur_x,cur_y
*/ 
function isCorner($x, $y, $cur_x, $cur_y) 
{ 
    if($x > $cur_x) 
    { 
        if($y > $cur_y) 
        { 
            if(isHindrance($x - 1, $y) || isHindrance($x, $y - 1)) 
            { 
                return true; 
            } 
        } 
        elseif($y < $cur_y) 
        { 
            if(isHindrance($x - 1, $y) || isHindrance($x, $y + 1)) 
            { 
                return true; 
            } 
        } 
    }

    if($x < $cur_x) 
    { 
        if($y < $cur_y) 
        { 
            if(isHindrance($x + 1, $y) || isHindrance($x, $y + 1)) 
            { 
                return true; 
            } 
        } 
        elseif($y > $cur_y) 
        { 
            if(isHindrance($x + 1, $y) || isHindrance($x, $y - 1)) 
            { 
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
function isInHindrance($arr, $x, $y) 
{ 
    foreach($arr as $key=>$val) 
    { 
        if($val[0]==$x && $val[1]==$y) 
        { 
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
function removeNode($arr, $x, $y, $status='') 
{ 
    foreach($arr as $key=>$val) 
    { 
        if(isset($val['x']) && $val['x']==$x && isset($val['y']) && $val['y']==$y) 
        { 
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
function getG($begin_x, $begin_y, $parent_x, $parent_y) 
{ 
    global $cost_1,$cost_2; 
    if(($begin_x - $parent_x) * ($begin_y - $parent_y) != 0) 
    { 
        return $cost_2; 
    } 
    else 
    { 
        return $cost_1; 
    } 
}

/** 
* 计算H值   H = 从网格上那个方格移动到终点B的预估移动耗费。
* F = G + H
* @param (类型) (参数名) (描述) 
*/ 
function getH($begin_x, $begin_y, $end_x, $end_y, $cost=10) 
{ 
    $h_cost = abs(($end_x - $begin_x)*$cost); 
    $v_cost = abs(($end_y - $begin_y)*$cost);
    $c=$h_cost+$v_cost;
//     echo "$begin_x, $begin_y, $end_x, $end_y, $cost^^^";
// echo $h_cost.'---'.$v_cost.'-'.$c.'>>>';
// die();
    return $h_cost+$v_cost; 
}

/** 
* 对开启列表排序 
* 
* @param (类型) (参数名) (描述) 
*/ 
function sortOpenList($a, $b) 
{ 
    if ($a['F'] == $b['F']) return 0; 
    return ($a['F'] > $b['F']) ? -1 : +1; 
}

/** 
* 取得最小F值的点 
* 
* @param (类型) (参数名) (描述) 
*/ 
function getLowestFNode($open_arr) 
{ 
    usort($open_arr, "sortOpenList"); 
    $node = array(); 
    $i = 0; 
    foreach($open_arr as $key=>$val) 
    { 
        if($i == 0) 
        { 
            $node = $val; 
        } 
        else 
        { 
            if($val['F'] <= $node['F']) 
            { 
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
function isNodeClose($node_x, $node_y) 
{ 
    global $close_arr; 
    foreach($close_arr as $key=>$val) 
    { 
        if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) 
        { 
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
function isNodeOpen($node_x, $node_y) 
{ 
    global $open_arr; 
    foreach($open_arr as $key=>$val) 
    { 
        if(isset($val['x']) && $val['x'] == $node_x && isset($val['y']) && $val['y'] == $node_y) 
        {
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
function isHindrance($node_x, $node_y) 
{ 
    global $area; 
    if(isset($area[$node_x][$node_y]['status']) && $area[$node_x][$node_y]['status']==-1) 
    { 
    return true; 
    } 
    return false; 
}

/** 
* 检查某结点是否在寻路路径中 
* 
* @param (类型) (参数名) (描述) 
*/ 
function isInPath($parent_arr, $x, $y) 
{ 
    foreach($parent_arr as $key=>$val) 
    { 
        if(isset($val['x']) && $val['x'] == $x && isset($val['y']) && $val['y'] == $y) 
        { 
            return true; 
        } 
    } 
    return false; 
} 