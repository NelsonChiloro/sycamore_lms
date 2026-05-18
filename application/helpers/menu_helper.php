<?php
/**
 * Created by PhpStorm.
 * User: Techsoft
 * Date: 7/10/2019
 * Time: 3:18 PM
 */


if(!function_exists('display_menu_admin')) {

	function display_menu_admin($parent, $level, $toggle) {

		$ci =& get_instance();
		$ci->load->database();
		$ci->load->library('session');
		$ci->load->model('menu_model');

		// $mm=$ci->session->userdata('mid');
		$mm= 1;
//		if($mm==1){
		$result = $ci->db->query("SELECT a.id,a.roll, a.label,a.icon_color, a.type, a.link,a.icon, Deriv1.Count FROM `menu` a  LEFT OUTER JOIN (SELECT parent, COUNT(*) AS Count FROM `menu` GROUP BY parent) Deriv1 ON a.id = Deriv1.parent WHERE a.menu_type_id = 1 AND   a.parent=" . $parent." AND active = 1   order by `sort` ASC")->result();

//		}else{
//			$result = $ci->db->query("SELECT a.id,a.roll, a.label,a.icon_color, a.type, a.link,a.icon, Deriv1.Count FROM `menu` a  LEFT OUTER JOIN (SELECT parent, COUNT(*) AS Count FROM `menu` GROUP BY parent) Deriv1 ON a.id = Deriv1.parent WHERE a.menu_type_id = 1 AND   a.parent=" . $parent." AND active = 1  AND a.roll='$mm' order by `sort` ASC")->result();
//
//		}

        $mm = array();

        foreach ($ci->session->userdata('access') as $rr){
            array_push($mm, $rr->controllerid);
        }
		$ret = '';

		if ($result) {

			foreach ($result as $row) {
            $make_count = $ci->db->query("SELECT COUNT(*) as tt FROM menuitems WHERE mid=$row->id  AND  id != 113 AND inside = 'No' AND id IN (".implode(',',$mm).")")->row();

if($row->id == $toggle){
	$is_active = 'open';
}else{
	$is_active = "";
}
if($make_count->tt==0){
	$remove = 'display:none;';
}else{
    $remove = "";
}
				$ret .= '

 <li class="nav-item dropdown '.$is_active.' " style="'.$remove .'">
 <a class="dropdown-toggle " href="javascript:void(0);">
  <span class="icon-holder">
                                    <i class="fa '.$row->icon.'"></i>
                                </span>

						<span class="title" >  ' . $row->label .'</span>
						
							<span class="arrow">
                                    <i class="arrow-icon"></i>
                                </span>
					</a>
         
        
							<ul class="dropdown-menu">
          ';
				$result2 = $ci->db->query("Select * FROM menuitems WHERE mid=$row->id  AND  id != 113 AND inside = 'No' order by `sortt` ASC ")->result();

				$CI =& get_instance();

				$CI->load->library('session');
				foreach ($result2 as $row2) {
					$flag = false;
					foreach ($CI->session->userdata('access') as $r) {
						if ($r->controllerid == $row2->id) {
							$flag = true;
							break;
						}


					}
					if ($flag) {
						$ret .= '
               <li><a href="' . base_url() .$row2->method . '">' . $row2->label . '</a></li>
               
               ';
					} else {

					}
//               if($row2->method=="read"||$row2->method=="delete"||$row2->method=="update") {
//               }else{

//               }
				}

				$ret .= '
          </ul>
         
        </li>

';

			}
		}



		return $ret;
	}
}
