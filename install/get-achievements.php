<?php
# validate data
if (php_sapi_name() !== "cli") exit("Not in CLI Mode");
if ($argc < 2) exit(sprintf("Usage: php %s <achievements.xml>" . PHP_EOL, $argv[0]));
$filename = $argv[1];
if (!file_exists($filename)) exit(sprintf("File '%s' does not exist" . PHP_EOL, $filename));

$dom = new DOMDocument();
$dom->load($filename);
$achievements = $dom->getElementsByTagName("achievement");

echo "INSERT INTO `v3_achievements` (`id`, `name`) VALUES" . PHP_EOL;
foreach ($achievements as $i => $achievement)
{
    /** @var DOMElement $achievement */
    echo sprintf(
        "    (%s, '%s')%s" . PHP_EOL,
        $achievement->getAttribute("id"),
        str_replace("'", "''", $achievement->getAttribute("name")),
        $i == ($achievements->length - 1) ? ';' : ','
    );
}
