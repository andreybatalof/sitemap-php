<?php

namespace SitemapPHP;

use Exception;
use XMLWriter;

/**
 * Sitemap
 *
 * This class used for generating Google Sitemap files
 *
 * @package    Sitemap
 * @author     Osman Üngür <osmanungur@gmail.com>
 * @author     Andrey Batalov <andreybatalof@mail.ru>
 * @copyright  2009-2015 Osman Üngür
 * @copyright  2012-2015 Evert Pot (http://evertpot.com/)
 * @copyright  2021-? Andrey Batalov (https://batalov.online/)
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       http://github.com/evert/sitemap-php
 */
class Sitemap {

    /**
     *
     * @var XMLWriter
     */
    private $writer;
    private $domain;
    private $path;
    private $filename = 'sitemap';
    private $current_item = 0;
    private $current_sitemap = 0;
    private $outputType;

    const EXT = '.xml';
    const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    const MIN_PRIORITY = 0.0;
    const MEDIUM_PRIORITY = 0.5;
    const MAX_PRIORITY = 1;

    const CHANGE_FREQ_NEVER = 'never';
    const CHANGE_FREQ_HOURLY = 'hourly';
    const CHANGE_FREQ_DAILY = 'daily';
    const CHANGE_FREQ_WEEKLY = 'weekly';
    const CHANGE_FREQ_MONTHLY = 'monthly';
    const CHANGE_FREQ_YEARLY = 'yearly';
    const CHANGE_FREQ_ALWAYS = 'always';

    const OUTPUT_TYPE_FILE = 'file';
    const OUTPUT_TYPE_STRING = 'string';

    const ITEM_PER_SITEMAP = 50000;
    const SEPERATOR = '-';
    const INDEX_SUFFIX = 'index';

    /**
     * Sitemap constructor.
     * @param $domain
     * @param string $outputType
     */
    public function __construct($domain, $outputType = self::OUTPUT_TYPE_FILE) {
        $this->setDomain($domain);
        $this->outputType = $outputType;
    }

    /**
     * Sets root path of the website, starting with http:// or https://
     *
     * @param string $domain
     * @return Sitemap
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Returns root path of the website
     *
     * @return string
     */
    private function getDomain() {
        return $this->domain;
    }

    /**
     * Returns XMLWriter object instance
     *
     * @return XMLWriter
     */
    private function getWriter() {
        return $this->writer;
    }

    /**
     * Assigns XMLWriter object instance
     *
     * @param XMLWriter $writer
     */
    private function setWriter(XMLWriter $writer) {
        $this->writer = $writer;
    }

    /**
     * Returns path of sitemaps
     *
     * @return string
     */
    private function getPath() {
        return $this->path;
    }

    /**
     * Sets paths of sitemaps
     *
     * @param string $path
     * @return Sitemap
     */
    public function setPath($path) {
        $this->path = $path;
        return $this;
    }

    /**
     * Returns filename of sitemap file
     *
     * @return string
     */
    private function getFilename() {
        return $this->filename;
    }

    /**
     * Sets filename of sitemap file
     *
     * @param string $filename
     * @return Sitemap
     */
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Returns current item count
     *
     * @return int
     */
    private function getCurrentItem() {
        return $this->current_item;
    }

    /**
     * Increases item counter
     *
     */
    private function incCurrentItem() {
        $this->current_item = $this->current_item + 1;
    }

    /**
     * Returns current sitemap file count
     *
     * @return int
     */
    private function getCurrentSitemap() {
        return $this->current_sitemap;
    }

    /**
     * Increases sitemap file count
     *
     */
    private function incCurrentSitemap() {
        $this->current_sitemap = $this->current_sitemap + 1;
    }

    /**
     * Prepares sitemap XML document
     *
     */
    public function startSitemap() {
        $writer = new XMLWriter();
        $this->setWriter($writer);

        if($this->outputType == self::OUTPUT_TYPE_FILE){
            if ($this->getCurrentSitemap()) {
                $writer->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . $this->getCurrentSitemap() . self::EXT);
            } else {
                $writer->openURI($this->getPath() . $this->getFilename() . self::EXT);
            }
        }else{
            $writer->openMemory();
        }

        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', self::SCHEMA);
    }

    /**
     * Adds an item to sitemap
     *
     * @param string $loc URL of the page. This value must be less than 2,048 characters.
     * @param string|null $priority The priority of this URL relative to other URLs on your site. Valid values range from 0.0 to 1.0.
     * @param string|null $changefreq How frequently the page is likely to change. Valid values are always, hourly, daily, weekly, monthly, yearly and never.
     * @param string|int|null $lastmod The date of last modification of url. Unix timestamp or any English textual datetime description.
     * @return Sitemap
     */
    public function addItem($loc, $priority = self::MEDIUM_PRIORITY, $changefreq = NULL, $lastmod = NULL) {
        if (($this->getCurrentItem() % self::ITEM_PER_SITEMAP) == 0) {
            if ($this->getWriter() instanceof XMLWriter) {
                $this->endSitemap();
            }
            $this->startSitemap();
            $this->incCurrentSitemap();
        }
        $this->incCurrentItem();
        $this->getWriter()->startElement('url');
        $this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
        if($priority !== null)
            $this->getWriter()->writeElement('priority', $priority);
        if ($changefreq)
            $this->getWriter()->writeElement('changefreq', $changefreq);
        if ($lastmod)
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
        $this->getWriter()->endElement();
        return $this;
    }

    /**
     * Prepares given date for sitemap
     *
     * @param string $date Unix timestamp or any English textual datetime description
     * @return string Year-Month-Day formatted date.
     */
    private function getLastModifiedDate($date) {
        if (ctype_digit($date)) {
            return date('Y-m-d', $date);
        } else {
            $date = strtotime($date);
            return date('Y-m-d', $date);
        }
    }

    /**
     * Finalizes tags of sitemap XML document.
     *
     */
    public function endSitemap() {
        if (!$this->getWriter()) {
            $this->startSitemap();
        }
        $this->getWriter()->endElement();
        $this->getWriter()->endDocument();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getSitemapString()
    {
        if ($this->outputType !== self::OUTPUT_TYPE_STRING) {
            throw new Exception('Bad output type');
        }
        return $this->getWriter()->outputMemory();
    }

    /**
     * 	/**
     * Writes Google sitemap index for generated sitemap files
     *
     * @param string $loc Accessible URL path of sitemaps
     * @param string|int $lastmod The date of last modification of sitemap. Unix timestamp or any English textual datetime description.
     * @return string|void if outputType file - void, else - xml-sitemap
     */
    public function createSitemapIndex($loc, $lastmod = 'Today') {
        if($this->outputType == self::OUTPUT_TYPE_FILE){
            $loc = $loc . $this->getFilename() . ($index ? self::SEPERATOR . $index : '') . self::EXT;
            $this->generateSitemap($loc, 'openFile', null, $lastmod);
            return;
        }else{
            $this->generateSitemap($loc, 'startMemory', $this->getWriter(), $lastmod);
            return $this->getWriter()->outputMemory();
        }
    }

    protected function openFile(XMLWriter $indexwriter){
        $indexwriter->openURI($this->getPath() . $this->getFilename() . self::SEPERATOR . self::INDEX_SUFFIX . self::EXT);
    }

    protected function startMemory(XMLWriter $indexwriter)
    {
        $indexwriter->openMemory();
    }

    protected function generateSitemap($loc, $openFunctionName, XMLWriter $writer = null, $lastmod = 'Today')
    {
        $this->endSitemap();
        $indexwriter = (empty($writer)) ? new XMLWriter() : $writer;
        $this->$openFunctionName($indexwriter);
        $indexwriter->startDocument('1.0', 'UTF-8');
        $indexwriter->setIndent(true);
        $indexwriter->startElement('sitemapindex');
        $indexwriter->writeAttribute('xmlns', self::SCHEMA);
        for ($index = 0; $index < $this->getCurrentSitemap(); $index++) {
            $indexwriter->startElement('sitemap');
            $indexwriter->writeElement('loc', $loc);
            $indexwriter->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
            $indexwriter->endElement();
        }
        $indexwriter->endElement();
        $indexwriter->endDocument();
    }
}
