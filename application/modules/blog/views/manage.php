  <h1>Manage Blog</h1>

  <?php
  if (isset($flash)) {
    echo $flash;
  }
  $create_blog_url = base_url()."blog/create";
  ?><p style="margin-top: 30px;">
    <a href="<?= $create_blog_url ?>"><button class="btn btn-primary" type="submit">Create New Blog Entry</button></a>

    <div class="row-fluid sortable">
      <div class="box span12">
        <div class="green-panel" data-original-title>
          <h2 class="mb"><i class="fa fas fa-file"></i> Custom Blog</h2>

        </div>
        <div class="box-content">
              <table class="table table-bordered table-striped table-condensed">
            <thead>
              <tr>
                <th>Thumnail</th>
                <th>Date Published</th>
                <th>Author</th>
                <th>Blog URL</th>
                <th>Blog Headline</th>
                <th class="span2" >Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $this->load->module('timedate');
              $this->load->module('blog_pics');
              foreach($query->result() as $row) {
                $edit_blog_url = base_url()."blog/create/".$row->id;
                $view_blog_url = base_url()."blog/view_blog/".$row->blog_url;
                $picture_name = $this->blog_pics->get_first_picture_name_by_blog_id($row->id);
                $date_published = $this->timedate->get_date($row->date_published, 'datepicker_us');
                ?>
                <tr>
                  <td><img src="<?= base_url() ?>media/blog_small_pics/<?= $picture_name ?>" alt="<?= $picture_name ?>"></td>
                  <td><?= $date_published ?></td>
                  <td><?= $row->author ?></td>
                  <td>
                    <a href="<?= $view_blog_url ?>">
                    <?= $view_blog_url ?>
                  </a></td>
                  <td class="center"><?= $row->blog_title ?></td>
                  <td class="center">
                    <a class="btn btn-success" href="<?= $view_blog_url ?>">
                      <i class="fa fas fa-search"></i>&nbsp;&nbsp; View
                    </a>
                    <a class="btn btn-info" href="<?= $edit_blog_url ?>">
                      <i class="fa fas fa-edit"></i>&nbsp;&nbsp; Edit
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
