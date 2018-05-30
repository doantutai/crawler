<?php
/**
 * Lorem ipsum implementation for PHP.
 *
 * @example See file "example.php" for usage examples
 * @author Artur Barseghyan www@foreverchild.info
 * @version 0.1
 * @copyright Artur Barseghyan
 * @license GPL
 */

class Lipsum {
    /**
     * Cache of generated lipsum strings. There are 4 separate caches for every
     * type of the lipsum: paragraphs / words / bytes / lists.
     *
     * @var Array
     */
    private $cache = array();


    /**
     * Allowed what values.
     *
     * @var Array of Strings
     */
    static public $whats = array('words', 'paragraphs', 'lists', 'bytes');

    /**
     * Amount of words / paragraphs / lists / bytes
     *
     * @var Int
     */
    private $amount;


    /**
     * Type of content to be generated: words / paragraphs / lists / bytes
     *
     * @var String
     */
    private $what;


    /**
     * If set to true, punctuation is added.
     *
     * @var Bool
     */
    private $punctuation = true;


    /**
     * If set to true, starts with "Lorem ipsum dolor sit amet".
     *
     * @var Bool
     */
    private $start = false;


    /**
     * If set to true, html tags are shown.
     *
     * @var Bool
     */
    private $tags = false;


    /**
     * Result of lorem ipsum operation. TODO - probably questionable - may be
     * replaced with $last - last result of lipsum operation.
     *
     * @var String
     */
    private $text;


    /**
     * Constructor.
     *
     * @param Int $amount
     * @param String $what
     * @param Bool $punctuation
     * @param Bool $tags
     * @param Bool $start
     */
    public function __construct($amount = 3, $what = 'paragraphs', $punctuation = true, $tags = false, $start = false) {
        $this->amount       = (int)trim($amount);
        $this->what         = self::ValidateWhat($what);
        $this->punctuation  = $punctuation;
        $this->tags         = $tags;
        $this->start        = $start;
    }


    /**
     * Renders another lorem ipsum.
     *
     * @param Bool $stripTags
     * @return String
     */
    public function render($stripTags = false) {
        $this->cache[$this->what][] = $lipsum = self::Generate($this->amount, $this->what, $this->punctuation, $this->tags, $this->start);
        return ($stripTags) ? strip_tags($lipsum) : $lipsum;
    }


    /**
     * Returns a random number from cache, respectively to the value of $what
     * given.
     *
     * @return String
     */
    public function random($what = 'paragraphs', $punctuation = true, $tags = false, $start = false) {
        $what = self::ValidateWhat($what);
        return isset($this->cache[$what]) ? self::Finalize($this->cache[$what][rand(0, count($this->cache[$what]) - 1)]) : null;
    }


    /**
     * Generates lorem ipsum string.
     *
     * @param Int $amount
     * @param String $what
     * @param Bool $punctuation
     * @param Bool $tags
     * @param Bool $start
     * @return String
     */
    static public function Generate($amount = 3, $what = 'paragraph', $punctuation = true, $tags = false, $start = false) {
        // Simple validation
        $amount       = (int)trim($amount);
        $what         = self::ValidateWhat($what);

        // Getting raw xml output
        $lipsumRawXml = @implode('', @file('http://www.lipsum.com/feed/xml?amount=' . $amount . '&what=' . $what . ($start ? '&start=yes' : '&start=no')));

        // Making an object of raw xml data
        $lipsumXml = simplexml_load_string($lipsumRawXml);

        // Performing last operations
        $lipsum = isset($lipsumXml->lipsum) ? self::Finalize($lipsumXml->lipsum, $what, $punctuation, $tags, $start) : null;
        
        return $lipsum;
    }


    /**
     * Finalizes the output.
     *
     * @param String $string
     * @param String $what
     * @param Bool $punctuation
     * @param Bool $tags
     * @param Bool $start
     * @return String
     */
    static public function Finalize($lipsum, $what = 'paragraph', $punctuation = true, $tags = false, $start = false) {
        $lipsum = (string)$lipsum;
        
        $what = self::ValidateWhat($what);

        if ($tags) :
            // If tags set to true, we shall add appropriate tags to paragraphs
            // generated. They are separated by PHP_EOL in the $lipsum
            // variable.

            switch($what) :
                case 'paragraphs' :
                    $lipsum = self::AddTags($lipsum, 'p');
                    break;
                case 'lists' :
                    $lipsum = self::AddTags($lipsum, 'li', 'ul');
                    break;
                case 'words' :
                    $lipsum = self::AddTags($lipsum, '');
                    break;
            endswitch;
        endif;

        if ($punctuation) :
            $lipsum = self::AddPunctuation($lipsum);
        endif;

        return $lipsum;
    }


    /**
     * Adds tags.
     *
     * @param String $lipsum
     * @param String $tag
     * @param String $parentTag
     */
    static public function AddTags($lipsum, $tag, $parentTag = '') {
        $tags = array();
        $lines = explode("\n", $lipsum);
        $taggedLipsum = '';

        $openTag = '';
        $closeTag = '';
        
        if (trim($tag)) :
            $openTag = "<$tag>";
            $closeTag = "</$tag>";
        endif;

        foreach ($lines as $line) :
            $taggedLipsum .= $openTag . $line . $closeTag;
        endforeach;

        if (trim($parentTag)) :
            $taggedLipsum = "<$parentTag>$taggedLipsum</$parentTag>";
        endif;

        return $taggedLipsum;
    }


    /**
     * Adds punctuation. TODO - finish.
     *
     * @param String $string
     * @return String
     */
    static public function AddPunctuation($lipsum) {
        return $lipsum;
    }


    /**
     * Validates $what.
     *
     * @param String $what
     * @return String
     */
    static public function ValidateWhat($what) {
        $what = trim($what);
        if (in_array($what, self::$whats)) :
            return $what;
        else :
            return 'paragraphs';
        endif;
    }


    /**
     * Sets amount of $what to generate.
     *
     * @param Int $amount
     */
    public function setAmount($amount) {
        $this->amount = (int)trim($amount);
        return $this;
    }


    /**
     * Sets tag output to the given value.
     *
     * @param Bool $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
        return $this;
    }


    /**
     * Sets $what.
     *
     * @param String $what
     */
    public function setWhat($what) {
        $this->what = self::ValidateWhat($what);
        return $this;
    }

    /**
     * Sets $punctuation to the given value.
     *
     * @param Bool $punctuation
     */
    public function setPunctuation($punctuation) {
        $this->punctuation = $punctuation;
        return $this;
    }

}
?>