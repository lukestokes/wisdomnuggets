<?php

class Nugget {
    public $type;
    public $category;
    public $description;
    public $title;

    function __construct($type, $category, $description, $title = "") {
        $this->type = $type;
        $this->category = $category;
        $this->description = $description;
        $this->title = $title;
    }

    function createWordGroup() {
        $smallest_group = 3;
        $largest_group = 5;
        $words = str_word_count($this->description, 1);
        if (count($words) <= 8) {
            $smallest_group = 2;
            $largest_group = 3;
        }
        if (count($words) > 40) {
            $smallest_group = 5;
            $largest_group = 8;
        }
        $grouped_words = array();
        $group_size = rand($smallest_group,$largest_group);
        $group = "";
        foreach ($words as $key => $value) {
            $group .= $value . " ";
            if (str_word_count($group,0) == $group_size) {
                $grouped_words[] = $group;
                $group_size = rand($smallest_group,$largest_group);
                $group = "";
            }
        }
        if ($group != "") {
            $grouped_words[] = $group;
        }
        return $grouped_words;
    }
}

class Chunk {
    public $type;
    public $description;
    public $url;
    public $nuggets = array();
    public $included = true;

    function __construct($type, $description, $url) {
        $this->type = $type;
        $this->description = $description;
        $this->url = $url;
    }

    function addNugget($category, $description, $title = "") {
        $this->nuggets[] = new Nugget($this->type, $category, $description, $title);
    }
}

class Wisdom {
    public $chunks = array();

    function addChunk($chunk) {
        $this->chunks[] = $chunk;
    }

    function getChunk($type) {
        foreach ($this->chunks as $chunk) {
            if ($chunk->type == $type) {
                return $chunk;
            }
        }
    }

    function getTypesAndCategories() {
        $types_and_categories = array();
        foreach ($this->chunks as $chunk) {
            if ($chunk->included) {
                foreach ($chunk->nuggets as $key => $nugget) {
                    $type_and_category = $chunk->type . "|" . $nugget->category;
                    if (!in_array($type_and_category, $types_and_categories)) {
                        $types_and_categories[] = $type_and_category;
                    }
                }
            }
        }
        return $types_and_categories;
    }

    function printAutoplayOptions($auto_play) {
        $auto_play_options = array(
          0 => '(no autoplay)',
          1 => '1 second',
          3 => '3 seconds',
          5 => '5 seconds',
          10 => '10 seconds',
        );
        foreach ($auto_play_options as $key => $value) {
            $selected = ($key == $auto_play) ? " selected" : "";
            print "<option" . $selected . " value=\"" . $key . "\">" . $value . "</option>\n";
        }
    }

    function printTypeCategoryOptions($selected_type_category) {
        $selected = ($selected_type_category == "") ? " selected" : "";
        print "<option" . $selected . " value=\"\">(no filter)</option>\n";
        foreach ($this->getTypesAndCategories() as $key => $type_category) {
            $selected = ($selected_type_category == $type_category) ? " selected" : "";
            $type_and_category = explode("|", $type_category);
            print "<option" . $selected . " value=\"" . $type_category . "\">" . ucwords($type_and_category[0]) . ": " . $type_and_category[1] . "</option>\n";
        }
    }

    function getChunkTypes() {
        $chunk_types = array();
        foreach ($this->chunks as $chunk) {
            $chunk_types[] = $chunk->type;
        }
        return $chunk_types;
    }

    function printChunkCheckboxes() {
        foreach ($this->chunks as $chunk) {
            $checked = $chunk->included ? " checked" : "";
            print "<div class=\"form-check\">";
            print "<input" . $checked . " class=\"form-check-input\" name=\"include_chunk_" . $chunk->type . "\" id=\"include_chunk_" . $chunk->type . "\" type=\"checkbox\">\n";
            print "<label class=\"form-check-label\" for=\"include_chunk_" . $chunk->type . "\">" . count($chunk->nuggets) . " " . $chunk->description . " <a href=\"" . $chunk->url . "\">(source)</a></label>\n";
            print "</div>";
        }
    }

    function getEntries($entry_type = "", $entry_category = "") {
        $entries = array();
        if ($entry_type != "") {
            $chunk = $this->getChunk($entry_type);
            if ($chunk->included) {
                if ($entry_category != "") {
                    foreach ($chunk->nuggets as $nugget) {
                        if ($nugget->category == $entry_category) {
                            $entries[] = $nugget;
                        }
                    }
                } else {
                    $entries = $chunk->nuggets;
                }
            }
            return $entries;
        } else {
            foreach ($this->chunks as $chunk) {
                if ($chunk->included) {
                    foreach ($chunk->nuggets as $nugget) {
                        $entries[] = $nugget;
                    }
                }
            }
            return $entries;
        }
    }

    function getRandom($entry_type = "", $entry_category = "") {
        $all_entries = $this->getEntries($entry_type, $entry_category);
        if (count($all_entries) == 0) {
            return new Nugget("empty","nothing","You've chosen nothing, and you shall have it.");
        }
        $key = array_rand($all_entries);
        return $all_entries[$key];
    }
}