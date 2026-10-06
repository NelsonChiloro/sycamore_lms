<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">User access grants</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Access</a>
				<span class="breadcrumb-item active">User access grants</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
                <!-- /.box-header -->
                <?php
				$id=$iddd;
                $this->load->model('Menuitems_model');
                $this->load->model('Roles_model');
                $this->load->model('Menu_model');
                $this->load->model('Access_model');
                $acc=$this->Access_model->get_all_acces($id);
                $result = $this->Menu_model->get_all();
                $roles=$this->Roles_model->get_all();
                ?>
                <div class="box-body">

                        <h2>Here You Can Give Users access to User group you have selected</h2><br/>
                    <form method="post" action="<?php echo base_url('access/give_menu')  ?>">
                   <span>
                       <select name="id" class="" required>
                           <option value="">--select Position--</option>
                    <?php
                    foreach ($roles as $r){


                        ?>

                            <option value="<?php echo $r->id; ?>"  <?php if($r->id==$this->session->userdata('roless')) { echo "selected";} ?>> <?php echo $r->RoleName;  ?>
                            </option>

                        <?php
                    }
                    ?>
                    </select><input type="submit" value="Select" name="Submit"></span>
                </form>
                    <br/><br/>
					<div style="overflow-x: scroll;">
						<form method="post" action="<?php echo base_url('access/addmenu')  ?>">
                       <table class="table-responsive" border="1" >

                           <tr>
                               <?php
                               foreach ($result as $hea) {

                               ?>
                                   <th><?php echo $hea->label; ?></th>
                               <?php  } ?>
                           </tr>
                           <tr>

                           <?php
                           foreach ($result as $head) {
                           $d = $this->Menuitems_model->get_menu($head->id);
                           ?>
                               <td>
                                   <ul>

                                   <?php

                                   foreach ($d as $menu) {
                                       $selected = false;
                                       foreach ($acc as $accc){
                                           if ($accc->controllerid == $menu->id) {
                                               $selected = true;
                                               break;
                                           }
                                       }

                                       ?>

    <li><input class="check" name='menu_id[]' type='checkbox' value='<?php echo $menu->id ?>' <?php if($selected){ echo 'checked';}   ?>><?php echo $menu->label ?></li>



                                       <?php

                                   }
                                   ?>

                               <input type="hidden" name="id" value="<?php echo $id; ?>">



                                   </ul>
                                     </td>


                <?php
                }
                ?>


</tr>

                      </table>
						<br>
						<input type="submit" value="Save changes" name="submit" class="btn btn-warning">
						</form>
					</div>
				</div>
		</div>
	</div>
</div>
