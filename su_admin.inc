<?php

function show_projects() {
    $projects = SUproject::enum();
    if ($projects) {
        start_table('table-striped');
        row_heading_array(array(
            'Name<br><small>click for details</small>',
            'URL',
            'created',
            'allocation'
        ));
        foreach ($projects as $p) {
            table_row(
                '<a href="su_manage.php?action=show_project&id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                date_str($p->create_time),
                $p->allocation
            );
        }
        end_table();
    } else {
        echo 'No projects yet';
    }
    echo '<p><a class="btn btn-default" href="su_manage.php?action=add_project_form">Add project</a>
    ';
    echo "<p></p>";
}

?>
