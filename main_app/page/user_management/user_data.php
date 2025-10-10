<!-- Content Header (Page header) -->
<section class="content-header">
 <div class="container-fluid">
   <div class="row mb-2">
     <div class="col-sm-6">
       <h1>MANAGEMENT USER</h1>
     </div>
     <div class="col-sm-6">
       <ol class="breadcrumb float-sm-right">
         <li class="breadcrumb-item"><a href="main_app.php?page=beranda">Home</a></li>
         <li class="breadcrumb-item active">User Management</li>
       </ol>
     </div>
   </div>
 </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
 <div class="container-fluid">
   <div class="row">
     <div class="col-12">
       <div class="card">
           <div class="card-header">
               <div class="card-tools" style="float: left; text-align: left;">
                 <a href="main_app.php?page=user_create" class="btn btn-tool btn-sm" style="background:rgba(0, 123, 255, 1)">
                   <i class="fas fa-plus-square" style="color: white;"> Tambah User</i>
                 </a>
               </div>
               <div class="card-tools" style="float: right; text-align: right;">
                 <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
                       <i class="fas fa-bars"></i>
                 </a>
               </div>
           </div>
           <!-- /.card-header -->
           <div class="card-body">
               <table id="example1" class="table table-bordered table-striped">
                   <thead style="background:rgb(129, 2, 0, 1)">
                     <tr>
                       <th style="text-align: center; color: white;">No</th>
                       <th style="text-align: center; color: white;">Username</th>
                       <th style="text-align: center; color: white;">Nama Lengkap</th>
                       <th style="text-align: center; color: white;">Role</th>
                       <th style="text-align: center; color: white;">Status</th>
                       <th style="text-align: center; color: white;">Last Login</th>
                       <th style="text-align: center; color: white;">Action</th>
                     </tr>
                   </thead>
                   <tbody>
                   <?php
                   $query = mysqli_query($mysqli,"SELECT * FROM users ORDER BY id DESC")or die(mysqli_error($mysqli));
                   $n=1;
                   while ($data=mysqli_fetch_array($query)) {
                       $nn=$n++;
                   ?>
                     <tr>
                       <td><?php echo $nn ?></td>
                       <td><?php echo htmlspecialchars($data['username']) ?></td>
                       <td><?php echo htmlspecialchars($data['nama_lengkap']) ?></td>
                       <td>
                           <span class="badge badge-<?php echo $data['role'] == 'Admin' ? 'danger' : ($data['role'] == 'Manager' ? 'warning' : 'info') ?>">
                               <?php echo htmlspecialchars($data['role']) ?>
                           </span>
                       </td>
                       <td>
                           <span class="badge badge-<?php echo $data['status'] == 'Aktif' ? 'success' : 'secondary' ?>">
                               <?php echo htmlspecialchars($data['status']) ?>
                           </span>
                       </td>
                       <td><?php echo $data['last_login'] ? date('d/m/Y H:i', strtotime($data['last_login'])) : '-' ?></td>
                       <td>
                           <span>
                               <a href="main_app.php?page=user_edit&id=<?=$data['id']?>" class="btn btn-success btn-sm">
                                   <i class="fa fa-pencil"></i> Edit
                               </a>
                           </span>
                           <?php if ($data['id'] != $_SESSION['user_id']) { ?>
                           <span>
                               <a onclick="return confirm ('Yakin hapus user <?php echo $data['nama_lengkap'];?>?')" href="main_app.php?page=user_delete&id=<?=$data['id']?>" class="btn btn-danger btn-sm">
                               <i class="fa fa-trash"></i> Hapus</a>
                           </span>
                           <?php } ?>
                       </td>
                     </tr>
                   <?php } //end while ?>
                   </tbody>
               </table>
           </div>
           <!-- /.card-body -->
       </div>
       <!-- /.card -->
   </div>
   </div>
 </div>
</section>

