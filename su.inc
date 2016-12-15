<?php

// show select for keywords

function keyword_select ($keywords, $category) {
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        echo '"<option value="'.$k->id.'">'.$k->name.'
        ';
    }
}
