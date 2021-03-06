<h1>Manage Items</h1>
<?php
if (isset($flash)) {
  echo $flash;
}
$create_item_url = base_url()."store_items/create";
?>
  <a href="<?= $create_item_url ?>"><button class="btn btn-primary" type="submit">Add New Item</button></a>

  <div class="row-fluid sortable">
    <div class="box span12">
      <div class="green-panel" data-original-title>
            <h2><i class="fa fas fa-tag"></i> Items Inventory</h2>
          </div>
          <div class="box-content">
            <?= $pagination ?>
            <table class="table table-striped table-bordered bootstrap-datatable datatable">
              <thead>
            <tr>
              <th>Item Title</th>
              <th>User ID</th>
              <th>Price</th>
              <th>Was Price</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach($query->result() as $row) {
              $edit_item_url = base_url()."store_items/create/".$row->id;
              $view_item_url = base_url()."store_items/view_item/".$row->item_url;
              $user_id = $row->user_id;
              if ($user_id == 0) {
                $user_name = "Admin";
              } else {
                $this->load->module('users');
                $user_query = $this->users->get_where($user_id);
                if ($user_query->num_rows() == 0) {
                  $user_name = "Unknown";
                } else {
                  $user_name = $this->users->get_where($user_id)->row()->user_name;
                }
              }
              $status = $row->status;
              $status_string = "";
              if ($status == 1) {
                $status_label = "success";
                $status_desc = "Active";
              } else {
                $status_label = "default";
                $status_desc = "Inactive";
              }
              ?>
              <tr>
                <td><?= $row->item_title ?></td>
                <td><?= $user_name ?></td>
                <td class="center"><?= $row->item_price ?></td>
                <td class="center"><?= $row->was_price ?></td>
                <td>
                  <span class="label label-<?= $status_label ?>"><?= $status_desc ?></span>
                </td>
                <td class="center">
                  <a class="btn btn-success" href="<?= $view_item_url ?>">
                    <i class="fa fa-laptop"></i>&nbsp;&nbsp;View
                  </a>
                  <a class="btn btn-info" href="<?= $edit_item_url ?>">
                    <i class="fa fas fa-edit"></i>&nbsp;&nbsp;Edit
                  </a>
                </td>
              </tr>
              <?php
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
