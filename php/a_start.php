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
$is_agree = $_REQUEST['is_agree']==1?1:0;// 是否允许斜向通过

if (!$location_begin) {
    exit(json_encode(constants(-1001)));
}
if (!$location_end) {
    exit(json_encode(constants(-1002)));
}

$location_begin = explode('-', $location_begin);
$location_end = explode('-', $location_end);
if (count($location_begin)<2) {
    exit(json_encode(constants(-1001)));
}
if (count($location_end)<2) {
    exit(json_encode(constants(-1002)));
}

// 地图大小
$map_width = $map_width;  // x
$map_height = $map_height; // y

// 是否允许障碍物边界斜向通过 
$is_agree = $is_agree; // 0/1

// 消耗 
$cost = array(10, 14); //左右, 对角 消耗值 

// 设置起始和结束坐标 
$location_begin = $location_begin;
$location_end = $location_end;

// 设置障碍物坐标 
$hindrance = array(); 
if ($location_hindrance) {
    $location_hindrance = array_filter(explode('|', $location_hindrance));
    foreach ($location_hindrance as $key => $val) {
        $hindrance[$key] = explode('-', $val);
    }
}

// 生成地图 并标记障碍物、起点、终点
$C = new createMap($map_width, $map_height, $hindrance);
$maps = $C->create_map();

// 生成路径
$P = new aStart($maps, $begin = $location_end, $end = $location_end);
$path = $P->create_path($is_agree = $is_agree);

//返回json
$ret = constants(0);
$ret['path'] = $path;
exit(json_encode($ret));



// 直接输出
// draw_maps($maps, $path);


/**
 * 创建地图类
 */
class createMap{

    public $width;  // [地图宽]
    public $height; // [地图高]
    public $hindrance; // [障碍物坐标集合]
     
    public function __construct($width, $height, $hindrance) {
        $this->width = $width; 
        $this->height = $height;
        $this->hindrance = $hindrance;
    }

    /** 
     *  
     * [createMap 创建地图 并标记出障碍物]
     * @param  [type] $width     地图宽
     * @param  [type] $height    地图高
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
    public function create_map() {
        
        $width = $this->width;
        $height = $this->height;
        $hindrance = $this->hindrance;
        // $begin_x = $begin[0]; 
        // $begin_y = $begin[1]; 
        // $end_x = $end[0]; 
        // $end_y = $end[1];

        $map = array(); 
        for($i=0; $i<$height; $i++) {

            for($j=0; $j<$width; $j++) {

                $map[$j][$i]['x'] = $j; 
                $map[$j][$i]['y'] = $i;

                $map[$j][$i]['status'] = 0;

                // 标记障碍物 
                if ($this->isInHindrance($hindrance, $j, $i)) { 
                    $map[$j][$i]['status'] = -1; 
                }

                /**
                // 标记起点 
                if ($j==$begin_x && $i==$begin_y) { 
                    $map[$j][$i]['status'] = 1; 
                }

                // 标记终点 
                if ($j==$end_x && $i==$end_y) { 
                    $map[$j][$i]['status'] = 9; 
                }
                **/
            } 
        }
        return $map; 
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
}


/**
 * a * 寻路
 */
class aStart{

    public $maps;  // [全地图]
    public $begin; // [起点坐标]
    public $end;   // [终点坐标]
    // public $hindrance; // [障碍物坐标集合]
    public $is_agree = 1; // 是否斜向
    public $cost = array(10, 14); // 正向、斜向 消耗值
    public $map_width; // 地图宽
    public $map_height; // 地图高
     
    public function __construct($maps, $begin, $end) {
        $this->maps = $maps; 
        $this->begin = $begin;
        $this->end = $end;
        // $this->hindrance = $hindrance;

        $this->is_agree = $is_agree; 
        $this->cost = $cost;

        $this->$map_width = count($maps); // 计算地图宽
        $this->$map_height = count($maps[0]); // 计算地图高
    }

    /**
     * [a_start 生成路径]
     * @param  integer $is_agree  [是否允许斜向]
     * @param  array   $cost      [消耗值]
     * @return [type]             [路径集合]
     */
    function create_path($is_agree = 1, $cost=array(10, 14)){

        $maps = $this->maps;
        $begin = $this->begin;
        $end = $this->end;
        // $hindrance = $this->hindrance;
        $map_width = $this->$map_width;
        $map_height = $this->$map_height;

        $begin_x = $begin[0];
        $begin_y = $begin[1];
        $end_x = $end[0];
        $end_y = $end[1];

        if ($is_agree) {
            $this->is_agree = $is_agree;
        }
        if ($cost) {
            $this->cost = $cost;
        }

        // 初始化 
        $open_arr = array(); // 开启坐标集合
        $close_arr = array(); // 关闭坐标集合
        $path = array(); // 路径坐标集合

        // 把起始格添加到开启列表 
        $one_H = $this->getH($begin_x,$begin_y,$end_x,$end_y);  // H = 从网格上那个方格移动到终点B的预估移动耗费。
        $open_arr[] = array(
                    'x' => $begin_x,
                    'y' => $begin_y,
                    'G' => 0,      // G = 从起点A，沿着产生的路径，移动到网格上指定方格的移动耗费。
                    'H' => $one_H,
                    'F' => $one_H,  // F = G + H
                    'p_node' => array($begin_x, $begin_y),
                );


        // 循环 
        while(1) {

            // 取得最小F值的格子作为当前格 
            $cur_node = $this->getLowestFNode($open_arr);

            // 从开启列表中删除此格子 
            $open_arr = $this->removeNode($open_arr, $cur_node['x'], $cur_node['y']);

            // 将当前点加入到关闭列表 
            $close_arr[] = $cur_node;
            
            //取周边节点
            $round_list = $this->getRoundNode($cur_node['x'], $cur_node['y'], $is_agree); 
            $round_num = count($round_list);
            // var_dump($round_list);die();
            
            for($i=0; $i<$round_num; $i++) {
                //所有周边节点中第i和节点的x,y
                $pos_arr = $round_list[$i];

                // 跳过已在关闭列表中的格子，障碍格子和 夹角转角格子 
                if(  $this->isOutMap($pos_arr[0], $pos_arr[1], $map_width, $map_height) 
                    ||  $this->isNodeClose($pos_arr[0], $pos_arr[1]) 
                    ||  $this->isHindrance($pos_arr[0], $pos_arr[1]) 
                    ||  $this->isCorner($pos_arr[0], $pos_arr[1], $cur_node['x'], $cur_node['y'])
                ){ 
                    continue; 
                }

                $new_g =  $this->getG($pos_arr[0],$pos_arr[1],$cur_node['x'],$cur_node['y']); 
                $total_g = $new_g + $cur_node['G'];

                // 如果节点已在开启列表中，重新计算一下G值 ，否则返回false
                $rs_open =  $this->isNodeOpen($pos_arr[0], $pos_arr[1]); 
                if(!$rs_open) { 

                    //不在opne列表
                    $val_H =  $this->getH($pos_arr[0], $pos_arr[1], $end_x, $end_y); 
                    $arr[$i] = array(
                            'x' => $pos_arr[0],
                            'y' => $pos_arr[1], 
                            'G' => $total_g,
                            'H' => $val_H,
                            'F' => $total_g + $val_H, 
                            'p_node' => array('x'=>$cur_node['x'], 'y'=>$cur_node['y'])
                        );
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

            // 到达终点
            if($cur_node['x'] == $end_x && $cur_node['y'] == $end_y) {

                $path =  $this->getPath($close_arr); 
                if(!empty($path)) { 

                    break; 
                } 
            }

            if(empty($open_arr)) { 
                break; 
            } 
        }
    }

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
        
        $begin_x = ($this->$begin)[0];
        $begin_y = ($this->$begin)[1];

        $path = array(); 
        $p = $close_arr[count($close_arr)-1]['p_node']; 
        $path[] = $p; 
        while(1) { 

            for($i=0; $i<count($close_arr); $i++) { 

                if($close_arr[$i]['x']==$p['x'] && $close_arr[$i]['y']==$p['y']) { 
                    $p = $close_arr[$i]['p_node']; 
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
     * [getRoundNode 取坐标周边节点 （包括斜向周边）] 
     *  需要切换 4方向 8方向
     * @param  [type] $x [X坐标]
     * @param  [type] $y [y坐标]
     * @return [type]    [description]
     */
    function getRoundNode($x, $y, $is_agree=1) { 
        // global $is_agree;

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
     * @param (类型) (参数名) (描述) 
     * @param (类型) (参数名) (描述) 
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
                if($this->isHindrance($x - 1, $y) || $this->isHindrance($x, $y - 1)) { 
                    return true; 
                } 
            } elseif($y < $cur_y) { 
                if($this->isHindrance($x - 1, $y) || $this->isHindrance($x, $y + 1)) { 
                    return true; 
                } 
            } 
        }

        if($x < $cur_x) { 
            if($y < $cur_y) { 
                if($this->isHindrance($x + 1, $y) || $this->isHindrance($x, $y + 1)) { 
                    return true; 
                } 
            } 
            elseif($y > $cur_y) { 
                if($this->isHindrance($x + 1, $y) || $this->isHindrance($x, $y - 1)) { 
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
    * @param (类型) (begin_x) (终点x) 
    * @param (类型) (begin_y) (终点y) 
    * @param (类型) (parent_x) (当前坐标x) 
    * @param (类型) (parent_y) (当前坐标y) 
    */ 
    function getG($begin_x, $begin_y, $parent_x, $parent_y) { 
        $cost_1 = ($this->cost)[0];
        $cost_2 = ($this->cost)[1]; 
        if(($begin_x - $parent_x) * ($begin_y - $parent_y) != 0) { 
            return $cost_2; 
        } else { 
            return $cost_1; 
        } 
    }

    /** 
    * 计算H值   H = 从网格上那个方格移动到终点B的预估移动耗费。
    * F = G + H
    * @param (类型) (begin_x) (终点x) 
    * @param (类型) (begin_y) (终点y) 
    * @param (类型) (parent_x) (当前坐标x) 
    * @param (类型) (parent_y) (当前坐标y) 
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
    * @param (类型) (open_arr) (开启坐标集合) 
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
    * @param (类型) (node_x) (x坐标) 
    * @param (类型) (node_y) (y坐标) 
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
    * @param (类型) (node_x) (x坐标) 
    * @param (类型) (node_y) (y坐标) 
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
    * @param (类型) (node_x) (x坐标) 
    * @param (类型) (node_y) (y坐标) 
    */ 
    function isHindrance($node_x, $node_y) { 
        $area = $this->maps; 
        if(isset($area[$node_x][$node_y]['status']) && $area[$node_x][$node_y]['status']==-1) { 
            return true; 
        }
        return false; 
    }

    /** 
    * 检查某结点是否在寻路路径中 
    * 
    * @param (类型) ($parent_arr) (寻路路径集合) 
    * @param (类型) ($x) (x坐标) 
    * @param (类型) ($y) (y坐标) 
    */ 
    function isInPath($parent_arr, $x, $y) { 
        foreach($parent_arr as $key=>$val) {

            if(isset($val['x']) && $val['x'] == $x && isset($val['y']) && $val['y'] == $y) { 
                return true; 
            } 
        } 
        return false; 
    } 

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

function constants($code){
    $CONSTANTS = array(
                0=>'success',
                -1001=>'请选择起点',
                -1002=>'请选择终点'
            );
    return array(
                'c'=>$code,
                'msg'=>isset($CONSTANTS[$code])?$CONSTANTS[$code]:$CONSTANTS[0],
            );
}

