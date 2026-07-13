<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['username'])) {
    header("location:index.php");
    return false;
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Contest | <?= ucwords($_SESSION['company_name']) ?> - Admin Panel </title>
    <?php include 'include-css.php'; ?>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php include 'sidebar.php'; ?>
            <!-- page content -->
            <div class="right_col" role="main">
                <!-- top tiles -->
                <br />
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Create a Contest</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class='row'>
                                    <div class='col-md-12'>
                                        <form id="quiz_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                            <input type="hidden" id="add_contest" name="add_contest" required="" value="1" aria-required="true">
                                            <input type="hidden" id="start_date" name="start_date" required="" value="">
                                            <input type="hidden" id="end_date" name="end_date" required="" value="">
                                            <div class="form-group row">
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="name">Name</label>
                                                    <input type="text" id="name" name="name" required class="form-control">
                                                </div>
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="image">Image</label>
                                                    <input type='file' class="form-control" name="image" id="image" required>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="date">Contest Start & End Date</label>
                                                    <input type="text" id="date" name="date" required class="form-control">
                                                </div>
                                                <div class="col-md-6 col-sm-12">
                                                    <label for="entry">Entry Fee Points</label>
                                                    <input type="number" id="entry" name="entry" required class="form-control" placeholder="These points will be deducted from users wallet" min='0'>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <div class="col-md-12 col-sm-12">
                                                    <label for="description">Description</label>
                                                    <textarea id="description" name="description" required class="form-control"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-md-12 col-sm-12">
                                                    <label for="top_users">Distribute Prize to Top --- Users</label>
                                                    <input type="number" id="top_users" name="top_users" required class="form-control" placeholder="For Instance Top 10 users will be getting prize" min='0'>
                                                </div>
                                            </div>
                                            <div class="form-group row" id="top_winner">

                                            </div>
                                            <div class="ln_solid"></div>
                                            <div id="result"></div>
                                            <div class="form-group">
                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                    <button type="submit" id="submit_btn" class="btn btn-warning">Add New</button>
                                                </div>
                                            </div>
                                            <div class="ln_solid"></div>
                                        </form>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col-md-12 col-lg-12'>
                                        <div id="toolbar">
                                            <div class="col-md-3">
                                                <button class="btn btn-danger btn-sm" id="delete_multiple_contests" title="Delete Selected Contests"><em class='fa fa-trash'></em></button>
                                            </div>
                                        </div>
                                        <table aria-describedby="mydesc" class='table-striped' id='contest_list' data-toggle="table" data-url="get-list.php?table=contest" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="true" data-show-refresh="true" data-trim-on-search="false" data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true" data-toolbar="#toolbar" data-maintain-selected="true" data-show-export="false" data-export-types='["txt","excel"]' data-export-options='{
                                                   "fileName": "contest-list-<?= date('d-m-y') ?>",
                                                   "ignoreColumn": ["state"]	
                                                   }' data-query-params="queryParams_1">
                                            <thead>
                                                <tr>
                                                    <th scope="col" data-field="state" data-checkbox="true"></th>
                                                    <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                    <th scope="col" data-field="status" data-sortable="false">Status</th>
                                                    <th scope="col" data-field="name" data-sortable="true">Name</th>
                                                    <th scope="col" data-field="start_date" data-sortable="true">Start Date</th>
                                                    <th scope="col" data-field="end_date" data-sortable="true">End Date</th>
                                                    <th scope="col" data-field="image" data-sortable="false">Image</th>
                                                    <th scope="col" data-field="description" data-sortable="false" data-visible="false">Description</th>
                                                    <th scope="col" data-field="entry" data-sortable="true">Entry</th>
                                                    <th scope="col" data-field="top_users" data-sortable="true">Top Users</th>
                                                    <th scope="col" data-field="participants" data-sortable="true">Participants</th>
                                                    <th scope="col" data-field="total_question" data-sortable="true">Questions</th>
                                                    <th scope="col" data-field="prize_status" data-sortable="false">Prize Status</th>
                                                    <th scope="col" data-field="operate" data-sortable="false" data-events="actionEvents">Operate</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /page content -->
        <div class="modal fade" id='editStatusModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Update Status</h4>
                    </div>
                    <div class="modal-body">
                        <form id="update_status_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                            <input type='hidden' name="update_id" id="update_id" value='' />
                            <input type='hidden' name="update_contest_status" id="update_contest_status" value='1' />
                            <div class="form-group">
                                <label class="control-label col-md-3 col-sm-3 col-xs-12">Status</label>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div id="status" class="btn-group">
                                        <label class="btn btn-default" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="0"> Deactive
                                        </label>
                                        <label class="btn btn-primary" data-toggle-class="btn-primary" data-toggle-passive-class="btn-default">
                                            <input type="radio" name="status" value="1"> Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" id="update_btn1" class="btn btn-success">Update</button>
                                </div>
                            </div>
                        </form>
                        <div class="row">
                            <div class="col-md-offset-3 col-md-8" style="display:none;" id="result1"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id='editCategoryModal' tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit Contest Details</h4>
                    </div>
                    <div class="modal-body">
                        <form id="update_form" method="POST" action="db_operations.php" data-parsley-validate class="form-horizontal form-label-left">
                            <input type='hidden' name="update_contest" id="update_contest" value='1' />
                            <input type='hidden' name="contest_id" id="contest_id" value='' />
                            <input type='hidden' name="image_url" id="image_url" value='' />
                            <input type='hidden' name="start_date" id="update_start_date" value='' />
                            <input type='hidden' name="end_date" id="update_end_date" value='' />
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="name" id="update_name" placeholder="Category Name" class='form-control' required>
                            </div>
                            <div class="form-group">
                                <label for="update_date">Contest Start & End Date</label>
                                <input type="text" id="update_date" name="date" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="update_description" placeholder="Short Description" class='form-control' required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image">Image <small>( Leave it blank for no change )</small></label>
                                <input type="file" class="form-control" name="image" id="update_image" aria-required="true">
                            </div>
                            <div class="form-group">
                                <label for="entry">Entry Fee Points</label>
                                <input type="number" id="update_entry" name="entry" required class="form-control" placeholder="These points will be deducted from users wallet" min='0'>
                            </div>

                            <input type="hidden" id="id" name="id">
                            <div class="ln_solid"></div>
                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <button type="submit" id="update_btn" class="btn btn-success">Update</button>
                                </div>
                            </div>
                        </form>
                        <div class="row">
                            <div class="col-md-offset-3 col-md-8" style="display:none;" id="update_result"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- footer content -->
        <?php include 'footer.php'; ?>
        <!-- /footer content -->
    </div>
    </div>
    <!-- jQuery -->


    <script>
        $('#top_users').on('blur input', function() {
            var no_of = $(this).val();
            var myHtml = "";

            $('div#top_winner').empty();
            for (var i = 1; i <= no_of; i++) {
                myHtml = "<div class='col-md-2 col-sm-2 col-xs-12'>";
                myHtml += "<input name='points[]' type='number' placeholder='" + i + " winner Prize' min='0' required class='form-control'>";
                myHtml += "<input name='winner[]' type='hidden' value=" + i + ">";
                myHtml += "<div>";
                for (var j = 6; j <= no_of; j++) {
                    if (i == j) {
                        myHtml += "<br/>";
                    }
                }
                $('div#top_winner').append(myHtml);
            }
        });
    </script>
    <script>
        $('#delete_multiple_contests').on('click', function(e) {
            sec = 'contest';
            is_image = 1;
            table = $('#contest_list');
            delete_button = $('#delete_multiple_contests');
            selected = table.bootstrapTable('getAllSelections');
            // alert(selected[0].id);
            ids = "";
            $.each(selected, function(i, e) {
                ids += e.id + ",";
            });
            ids = ids.slice(0, -1); // removes last comma character
            if (ids == "") {
                alert("Please select some contests to delete!");
            } else {
                if (confirm("Are you sure you want to delete all selected contests?")) {
                    $.ajax({
                        type: 'GET',
                        url: "db_operations.php",
                        data: 'delete_multiple=1&ids=' + ids + '&sec=' + sec + '&is_image=' + is_image,
                        beforeSend: function() {
                            delete_button.html('<i class="fa fa-spinner fa-pulse"></i>');
                        },
                        success: function(result) {

                            if (result == 1) {
                                alert("contests deleted successfully");
                            } else {

                                alert("Could not delete contests. Try again!");
                            }
                            delete_button.html('<i class="fa fa-trash"></i>');
                            table.bootstrapTable('refresh');
                        }
                    });
                }
            }
        });
    </script>
    <script>
        $('#date, #update_date').daterangepicker({

            timePicker: true,

            "showDropdowns": true,
            alwaysShowCalendars: true,
            showMeridian: false,
            use24hours: true,
            ranges: {
                'Today': [moment(), moment()],
                'Tommorow': [moment().add(1, 'days'), moment().add(1, 'days')],
                'Coming 7 Days': [moment(), moment().add(6, 'days')],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
            },
            startDate: moment().startOf("hour"),
            endDate: moment().startOf("hour").add(32, "hour"),
            locale: {
                format: "DD/MM/YY HH:mm:ss"
            }

        });
        $('#update_date').daterangepicker({
            timePicker: true,
            "showDropdowns": true,
            alwaysShowCalendars: true,
            ranges: {
                'Today': [moment(), moment()],
                'Tommorow': [moment().add(1, 'days'), moment().add(1, 'days')],
                'Coming 7 Days': [moment(), moment().add(6, 'days')],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
            },
            "locale": {
                "format": "YYYY/MM/DD HH:mm:ss",
                "separator": " - "
            }
        });
        var drp = $('#date').data('daterangepicker');
        $('#start_date').val(drp.startDate.format('YYYY/MM/DD HH:mm:ss '));
        $('#end_date').val(drp.endDate.format('YYYY-MM-DD HH:mm:ss '));
    </script>
    <script>
        $('#date').on('apply.daterangepicker', function(ev, picker) {
            var drp = $('#date').data('daterangepicker');
            // alert(drp.startDate.format('YYYY-MM-DD')+' '+drp.endDate.format('YYYY-MM-DD'));
            $('#start_date').val(drp.startDate.format('YYYY-MM-DD HH:mm:ss'));
            $('#end_date').val(drp.endDate.format('YYYY-MM-DD HH:mm:ss'));
        });
    </script>
    <script>
        window.actionEvents = {
            'click .edit-quiz': function(e, value, row, index) {
                // alert('You click remove icon, row: ' + JSON.stringify(row));

                $('#contest_id').val(row.id);
                $('#update_name').val(row.name);
                $('#update_description').val(row.description);
                $('#update_start_date').val(row.start_date);
                $('#update_end_date').val(row.end_date);
                $('#update_entry').val(row.entry);
                $('#update_date').data('daterangepicker').setStartDate(row.start_date);
                $('#update_date').data('daterangepicker').setEndDate(row.end_date);
                $('#image_url').val($(this).data('image'));
            },
            'click .edit-data': function(e, value, row, index) {
                $('#update_id').val(row.id);
                // alert('You click remove icon, row: ' + JSON.stringify(row));
                $("input[name=status][value=1]").prop('checked', true);
                if ($(row.status).text() == 'Deactive')
                    $("input[name=status][value=0]").prop('checked', true);
            }
        };
    </script>
    <script>
        $('#update_status_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#update_status_form").validate().form()) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#update_btn1').html('Please wait..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#result1').html(result);
                        $('#result1').show().delay(3000).fadeOut();
                        $('#update_btn1').html('Update');
                        $('#contest_list').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#editStatusModal').modal('hide');
                        }, 3000);
                    }
                });
            }
        });
    </script>
    <script>
        $('#update_date').on('apply.daterangepicker', function(ev, picker) {
            var udrp = $('#update_date').data('daterangepicker');
            // alert(udrp.startDate.format('YYYY-MM-DD')+' '+udrp.endDate.format('YYYY-MM-DD'));
            $('#update_start_date').val(udrp.startDate.format('YYYY-MM-DD HH:mm:ss'));
            $('#update_end_date').val(udrp.endDate.format('YYYY-MM-DD HH:mm:ss'));
        });
    </script>
    <script>
        $('#update_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#update_form").validate().form()) {
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: formData,
                    beforeSend: function() {
                        $('#update_btn').html('Please wait..');
                    },
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(result) {
                        $('#update_result').html(result);
                        $('#update_result').show().delay(4000).fadeOut();
                        $('#update_btn').html('Update');
                        $('#update_image').val('');
                        $('#contest_list').bootstrapTable('refresh');
                        setTimeout(function() {
                            $('#editCategoryModal').modal('hide');
                        }, 3000);
                    }
                });
            }
        });
    </script>
    <script>
        function queryParams_1(p) {
            return {
                "status": $('#filter_status').val(),
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }
    </script>
    <script>
        $('#quiz_form').validate({
            rules: {
                name: "required",
                date: "required",
                description: "required"
            }
        });
    </script>
    <script>
        $('#quiz_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            if ($("#quiz_form").validate().form()) {
                if (confirm('Are you sure?Want to create Contest')) {
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        beforeSend: function() {
                            $('#submit_btn').html('Please wait..');
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function(result) {
                            $('#result').html(result);
                            $('#result').show().delay(4000).fadeOut();
                            $('#submit_btn').html('Submit');
                            $('#quiz_form')[0].reset();
                            $('#contest_list').bootstrapTable('refresh');
                        }
                    });
                }
            }
        });
    </script>
    <script>
        $(document).on('click', '.delete-quiz', function() {
            if (confirm('Are you sure? Want to delete Contest? All related questions & leaderboard details will also be deleted')) {
                id = $(this).data("id");
                image = $(this).data("image");
                $.ajax({
                    url: 'db_operations.php',
                    type: "get",
                    data: 'id=' + id + '&image=' + image + '&delete_contest=1',
                    success: function(result) {
                        if (result == 1) {
                            $('#contest_list').bootstrapTable('refresh');
                        } else
                            alert('Error! Contest could not be deleted');
                    }
                });
            }
        });
    </script>
</body>

</html>