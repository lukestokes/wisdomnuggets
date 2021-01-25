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

	function split($smallest_group, $largest_group) {
		$words = str_word_count($this->description, 1);
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

class Wisdom {
	public $entries = array();

	function addNugget($type, $category, $description, $title = "") {
		if (!array_key_exists($type, $this->entries)) {
			$this->entries[$type] = array();
		}
		$this->entries[$type][] = new Nugget($type, $category, $description, $title);
	}

	function printStats() {
		print "Data Entries Per Type:<br />";
		foreach ($this->entries as $type => $nuggets) {
			print ucwords($type) . ": " . count($nuggets)  . "<br />\n";
		}
	}

	function getTypesAndCategories() {
		$types_and_categories = array();
		foreach ($this->entries as $type => $nuggets) {
			foreach ($nuggets as $key => $nugget) {
				$type_and_category = $type . "|" . $nugget->category;
				if (!in_array($type_and_category, $types_and_categories)) {
					$types_and_categories[] = $type_and_category;
				}
			}
		}
		return $types_and_categories;
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

	function getEntries($entry_type = "", $entry_category = "") {
		$all_entries = array();
		foreach ($this->entries as $type => $nuggets) {
			if ($entry_type == "") {
				$all_entries = array_merge($all_entries, $nuggets);
			} else {
				if ($entry_type == $type) {
					if ($entry_category == "") {
						return $nuggets;
					} else {
						$filtered_entries = array();
						foreach ($nuggets as $key => $nugget) {
							if ($nugget->category == $entry_category) {
								$filtered_entries[] = $nugget;
							}
						}
						return $filtered_entries;
					}
				}
			}
		}
		return $all_entries;
	}

	function getRandom($entry_type = "", $entry_category = "") {
		$all_entries = $this->getEntries($entry_type, $entry_category);
		$key = array_rand($all_entries);
		return $all_entries[$key];
	}

	function printRandom($entry_type = "") {
		$entry = $this->getRandom($entry_type);
		print $entry->type . "\n";
		print $entry->category . "\n";
		if ($entry->title) {
			print $entry->title . "\n";
		}
		print $entry->description . "\n";
	}
}