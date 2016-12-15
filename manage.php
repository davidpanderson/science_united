<?php

// management interface for SU

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/util.inc");
chdir($dir);

require_once("su_db.inc");

function show_projects() {
    page_head('Projects');
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
                '<a href="manage.php?action=show_project&id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                date_str($p->create_time),
                $p->allocation
            );
        }
        end_table();
    } else {
        echo 'No projects yet';
    }
    echo '<p><a class="btn btn-default" href="manage.php?action=add_project_form">Add project</a>
    ';
    echo "<p></p>";
    page_tail();
}

function array_to_string($arr) {
    $x = false;
    foreach ($arr as $wd) {
        if ($x) {
            $x .= ", $wd";
        } else {
            $x = $wd;
        }
    }
    return $x;
}

function su_show_project() {
    $id = get_int('id');
    $project = SUProject::lookup_id($id);
    page_head($project->name);
    start_table();
    row2("name", $project->name);
    row2("URL", $project->url);
    row2("Created", date_str($project->create_time));
    row2("Allocation", $project->allocation);
    $pks = SUProjectKeyword::enum("project_id=$project->id");
    $sci = array();
    $loc = array();
    if ($pks) {
        foreach ($pks as $pk) {
            $kwd = SUKeyword::lookup_id($pk->keyword_id);
            if ($kwd->category == SCIENCE) {
                $sci[] = $kwd->word;
            }
            if ($kwd->category == LOCATION) {
                $loc[] = $kwd->word;
            }
        }
    } else {
        $x = "None";
    }
    row2("Science keywords", array_to_string($sci));
    row2("Location keywords", array_to_string($loc));
    end_table();
    echo '<a class="btn btn-success" href="manage.php?action=project_edit_form&id='.$project->id.'">Edit</a>
    ';
    page_tail();
}

function keyword_subset($keywords, $category) {
    $x = array();
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        $x[] = array($k->id, $k->name);
    }
    return $x;
}

function add_project_form() {
    page_head("Add project");
    form_start('manage.php');
    form_input_hidden('action', 'add_project_action');
    form_input_text('URL', 'url');
    form_input_text('Name', 'name');
    form_input_text('Allocation', 'alloc', 'number');
    $keywds = SUKeyword::enum();
    $sci_keywds = keyword_subset($keywds, SCIENCE);
    $loc_keywds = keyword_subset($keywds, LOCATION);
    if (count($sci_keywds)>0) {
        form_select('Science keywords<br><small>use ctrl to select multiple</small>', 'sci_keywds', $sci_keywds, true);
    }
    if (count($loc_keywds)>0) {
        form_select('Location keywords<br><small>use ctrl to select multiple</small>', 'loc_keywds', $loc_keywds, true);
    }
    form_input_text('New science keywords<br><small>comma-separated</small>', 'new_sci_keywds');
    form_input_text('New location keywords<br><small>comma-separated</small>', 'new_loc_keywds');
    form_submit('OK');
    form_end();
    page_tail();
}

function add_keywords($str, $project_id, $category) {
    $x = explode(',', $str);
    $n = 0;
    foreach ($x as $w) {
        $w = trim($w);
        if ($w == '') continue;
        $kw_id = SUKeyword::insert("(word, category) values ('$w', $category)");
        SUProjectKeyword::insert("(project_id, keyword_id) values ($project_id, $kw_id)");
        $n++;
    }
    return $n;
}

function add_project_action() {
    $url = get_str('url');
    $name = get_str('name');
    $alloc = get_str('alloc');
    $now = time();
    $project_id = SUProject::insert(
        "(url, name, create_time, allocation) values ('$url', '$name', $now, $alloc)"
    );
    if (!$project_id) {
        error_page("insert failed");
    }
    $nsci = add_keywords(get_str('new_sci_keywds'), $project_id, SCIENCE);
    $nloc = add_keywords(get_str('new_loc_keywds'), $project_id, LOCATION);
    page_head("Project added");
    echo '
        Added project.
    ';
    if ($nsci) {
        echo "<p>Added $nsci science keywords.\n";
    }
    if ($nloc) {
        echo "<p>Added $nloc location keywords.\n";
    }
    echo '
        <p>
        <a href="manage.php">Return to management page</a>
    ';
    page_tail();

}

function edit_project_form() {
    $id = get_int('id');
    $project = SUProject::lookup_id($id);
    page_head('Edit '.$project->name);
    page_tail();
}

function edit_project_action() {
}

$action = get_str('action', true);

switch($action) {
case 'show_projects':
case null:
    show_projects();
    break;
case 'show_project':
    su_show_project();
    break;
case 'add_project_form':
    add_project_form();
    break;
case 'add_project_action':
    add_project_action();
    break;
case 'edit_project_form':
    add_project_action();
    break;
case 'edit_project_action':
    edit_project_action();
    break;
}

?>
