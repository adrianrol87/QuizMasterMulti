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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Word Search Levels | <?= ucwords($_SESSION['company_name']) ?> Admin Panel</title>
    <?php include 'include-css.php'; ?>
</head>
<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php include 'sidebar.php'; ?>
            <div class="right_col" role="main">
                <br />
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="x_panel">
                            <div class="x_title">
                                <h2>Word Search <small>import levels using CSV file</small></h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <?php
                                $db->sql("SHOW TABLES LIKE 'word_search_level'");
                                $moduleInstalled = !empty($db->getResult());
                                ?>
                                <?php if (!$moduleInstalled) { ?>
                                    <div class="alert alert-warning">
                                        <strong>Module not installed.</strong> First import
                                        <code>word-search-module.sql</code> in phpMyAdmin.
                                    </div>
                                <?php } ?>
                                <form id="word_search_import_form" method="POST" action="db_operations.php" class="form-horizontal form-label-left" enctype="multipart/form-data">
                                    <input type="hidden" name="import_word_search_levels" value="1" />
                                    <div class="form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12" for="levels_file">CSV Levels file</label>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <input type="file" name="levels_file" id="levels_file" required class="form-control col-md-7 col-xs-12" accept=".csv" <?= $moduleInstalled ? '' : 'disabled' ?> />
                                        </div>
                                    </div>
                                    <div class="ln_solid"></div>
                                    <div class="form-group">
                                        <div class="col-md-3 col-sm-6 col-xs-12 col-md-offset-3">
                                            <button type="submit" id="submit_btn" class="btn btn-success" <?= $moduleInstalled ? '' : 'disabled' ?>>Upload CSV file</button>
                                        </div>
                                        <div class="col-md-4 col-sm-6 col-xs-12">
                                            <a class="btn btn-warning" href="library/word-search-level-format.csv" target="_blank">
                                                <em class="fas fa-download"></em> Download Sample File Here
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                <div class="row">
                                    <div class="col-md-offset-3 col-md-6" style="display:none;" id="result"></div>
                                </div>
                            </div>
                        </div>

                        <div class="x_panel">
                            <div class="x_title">
                                <h2>CSV Format <small>one row = one level</small></h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <p><strong>category_name</strong> = category title for the level. If it does not exist, it will be created automatically.</p>
                                <p><strong>language_id</strong> = use your admin language ID. If language mode is disabled, this is stored as 0.</p>
                                <p><strong>level_number</strong> = level order inside the category.</p>
                                <p><strong>board_rows / board_cols</strong> = board size. Recommended: 15 x 15.</p>
                                <p><strong>time_limit</strong> = seconds for the level.</p>
                                <p><strong>reward_coins</strong> = coins awarded when the level is completed.</p>
                                <p><strong>words</strong> = exactly the words for the level separated with <code>|</code>.</p>
                                <p class="text-info">Example: <code>GATO|PERRO|LEON|TIGRE|ZORRO|PANDA|KOALA|MONO|LOBO|CEBRA</code></p>
                            </div>
                        </div>

                        <?php if ($moduleInstalled) { ?>
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Imported Levels <small>paginated, searchable and sortable</small></h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div id="toolbar">
                                        <button class="btn btn-danger btn-sm" id="delete_multiple_word_search_levels" title="Delete Selected Levels">
                                            <em class='fa fa-trash'></em> Delete Selected
                                        </button>
                                    </div>

                                    <table aria-describedby="word-search-levels" class="table-striped" id="word_search_level_list"
                                        data-toggle="table"
                                        data-url="get-list.php?table=word_search_level"
                                        data-click-to-select="true"
                                        data-side-pagination="server"
                                        data-pagination="true"
                                        data-page-list="[10, 20, 50, 100, 200]"
                                        data-search="true"
                                        data-show-columns="true"
                                        data-show-refresh="true"
                                        data-trim-on-search="false"
                                        data-sort-name="category_name"
                                        data-sort-order="asc"
                                        data-toolbar="#toolbar"
                                        data-mobile-responsive="true"
                                        data-maintain-selected="true">
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="state" data-checkbox="true"></th>
                                                <th scope="col" data-field="id" data-sortable="true">ID</th>
                                                <th scope="col" data-field="category_name" data-sortable="true">Category</th>
                                                <?php if ($fn->is_language_mode_enabled()) { ?>
                                                    <th scope="col" data-field="language" data-sortable="true">Language</th>
                                                <?php } ?>
                                                <th scope="col" data-field="level_number" data-sortable="true">Level</th>
                                                <th scope="col" data-field="board" data-sortable="false">Board</th>
                                                <th scope="col" data-field="time_limit" data-sortable="true">Time</th>
                                                <th scope="col" data-field="reward_coins" data-sortable="true">Coins</th>
                                                <th scope="col" data-field="words_count" data-sortable="true">Words</th>
                                                <th scope="col" data-field="status" data-sortable="false">Status</th>
                                                <th scope="col" data-field="operate" data-events="wordSearchLevelEvents">Operate</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
    <script>
        $('#word_search_import_form').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                beforeSend: function() {
                    $('#submit_btn').html('Uploading levels..');
                },
                cache: false,
                contentType: false,
                processData: false,
                success: function(result) {
                    $('#result').html(result);
                    $('#result').show().delay(8000).fadeOut();
                    $('#submit_btn').html('Upload CSV file');
                    $('#levels_file').val('');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                }
            });
        });

        window.wordSearchLevelEvents = {
            'click .delete-word-search-level': function(e, value, row) {
                var id = row.id;
                if (!confirm('Are you sure you want to delete this level?')) {
                    return;
                }

                $.ajax({
                    type: 'GET',
                    url: 'db_operations.php',
                    data: 'delete_word_search_level=1&id=' + id,
                    success: function(result) {
                        if (result == 1) {
                            $('#word_search_level_list').bootstrapTable('refresh');
                        } else {
                            alert('Could not delete level. Try again.');
                        }
                    }
                });
            }
        };

        $('#delete_multiple_word_search_levels').on('click', function() {
            var table = $('#word_search_level_list');
            var selected = table.bootstrapTable('getAllSelections');
            var ids = [];

            $.each(selected, function(i, item) {
                ids.push(item.id);
            });

            if (ids.length === 0) {
                alert('Please select some levels to delete!');
                return;
            }

            if (!confirm('Are you sure you want to delete this level?')) {
                return;
            }

            $.ajax({
                type: 'GET',
                url: 'db_operations.php',
                data: 'delete_multiple=1&ids=' + ids.join(',') + '&sec=word_search_level&is_image=0',
                success: function(result) {
                    if (result == 1) {
                        table.bootstrapTable('refresh');
                    } else {
                        alert('Could not delete selected levels. Try again.');
                    }
                }
            });
        });
    </script>
</body>
</html>
