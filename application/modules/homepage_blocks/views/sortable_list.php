<style type="text/css">
.sort {
  list-style: none;
  border: 1px #aaa solid;
  color: #333;
  padding: 10px;
  margin-bottom: 4px;
}
</style>
<ul id="sortlist">
  <?php
  $this->load->module('homepage_block');
  $this->load->module('homepage_offers');
  foreach($query->result() as $row) {
    $edit_item_url = base_url()."homepage_blocks/create/".$row->id;
    $view_item_url = base_url()."homepage_blocks/view/".$row->id;
    $block_title = $row->block_title;
    ?>
    <li class="sort" id="<?= $row->id?>"><i class="icon-sort"></i> <?= $row->block_title ?>
      <?= $block_title ?>
      <?php
      $num_items = $this->homepage_offers->count_where('block_id', $row->id);
        if ($num_items == 1) {
          $entity = "Homepage Offer";
        } else {
          $entity = "Homepage Offers";
        }
        $sub_cat_url = base_url()."homepage_blocks/manage/".$row->id;
        $update_id = $row->id;
        ?>
        <a class="btn btn-default" href="<?= base_url() ?>">
          <i class="fa fas fa-eye"></i>
          <?php
          echo $num_items." ".$entity;
          ?>
        </a>

      <a class="btn btn-info" href="<?= $edit_item_url ?>">
        <i class="fa fas fa-edit"></i>
      </a>
    </li>
    <?php
  }
  ?>
</ul>
