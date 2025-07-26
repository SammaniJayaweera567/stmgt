<?php
ob_start();
include '../../init.php';

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="d-flex content-header-text"><i class="fas fa-arrow-alt-circle-right" style="font-size: 20px;"></i>
            <h5 class="mb-5 w-auto">Manage Academic Sessions</h5>
        </div>
        <div class="col-12 mt-3">
            <ul class="nav nav-tabs" id="sessionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list"
                        type="button" role="tab">
                        <i class="fas fa-list me-1" style="font-size: 15px; margin-right: 5px;"></i> Session List
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button"
                        role="tab">
                        <i class="fas fa-plus-circle me-1" style="font-size: 15px; margin-right: 5px;"></i>
                        Add Session
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="sessionTabsContent">
                <!-- Session List Tab -->
                <div class="tab-pane fade show active mt-3" id="list" role="tabpanel">
                    <div class="card mt-5">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="sessionTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Session Name</th>
                                            <th>Status</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dummy Data Rows -->
                                        <tr>
                                            <td>1</td>
                                            <td>2024 - Term 1</td>
                                            <td>Open</td>
                                            <td>2024-01-10</td>
                                            <td>2024-03-20</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=2" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(2)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>2024 - Term 2</td>
                                            <td>Closed</td>
                                            <td>2024-04-01</td>
                                            <td>2024-06-15</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=2" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(2)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>2024 - Term 3</td>
                                            <td>Closed</td>
                                            <td>2024-07-01</td>
                                            <td>2024-09-10</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=2" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(2)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>4</td>
                                            <td>2024 - Term 4</td>
                                            <td>Closed</td>
                                            <td>2024-10-01</td>
                                            <td>2024-12-15</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="edit.php?id=2" class="btn btn-sm btn-primary mr-2">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(2)" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Add Session Tab -->
                <div class="tab-pane fade" id="add" role="tabpanel">
                    <div class="col-md-12 mt-5">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Session</h3>
                            </div>
                            <form method="post" action="add_session.php" class="p-4">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="session_name">Session Name</label>
                                        <input type="text" class="form-control" id="session_name" name="session_name"
                                            placeholder="Enter Session Name">
                                    </div>
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date">
                                    </div>
                                    <div class="form-group">
                                        <label for="is_open">Is Open?</label>
                                        <select class="form-control" id="is_open" name="is_open">
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- DataTables Init -->
<script>
$(document).ready(function() {
    $('#sessionTable').DataTable({
        responsive: true,
        lengthMenu: [5, 10, 25, 50, 100],
        pageLength: 10,
        language: {
            searchPlaceholder: "Search sessions...",
            search: "",
            lengthMenu: "Show _MENU_ entries"
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include '../layouts.php';
?>