<?php
Plugin::setInfos(array(
	'id'          => 'tumblr',
	'title'       => 'Tumblr',
	'description' => 'Adds in tumblr functionality. Call `tumblrPosts("userName", 1)` function to get an array of tumblr posts. See ReadMe.md for more info.',
	'version'     => '.1',
	'license'     => 'MIT',
	'author'      => 'A.J. Cates',
	'website'     => 'http://ajcates.com/',
	'require_wolf_version' => '0.5.0',
	'require_frog_version' => '0.9.3'
));

require 'Tumblr.php';



function tumblrPosts($userName, $page=1) {
	static $tumblr = false;
	if(!$tumblr) {
		$tumblr = new Tumblr();
	}
	return $tumblr->getPosts($userName, $page);
}
function printTumblrPosts($userName, $page=1) {
	static $tumblr = false;
	if(!$tumblr) {
		$tumblr = new Tumblr();
	}
	return $tumblr->printPosts($userName, $page);
}
function tumblrPost($userName, $id=1) {
	static $tumblr = false;
	if(!$tumblr) {
		$tumblr = new Tumblr();
	}
	return $tumblr->getPost($userName, $id);
}
function printTumblrPost($userName, $id=1) {
	static $tumblr = false;
	if(!$tumblr) {
		$tumblr = new Tumblr();
	}
	return $tumblr->printPost($userName, $id);
}
function tumblrInfo($username) {
	static $tumblr = false;
	if(!$tumblr) {
		$tumblr = new Tumblr();
	}
	return $tumblr->getInfo($username);
}