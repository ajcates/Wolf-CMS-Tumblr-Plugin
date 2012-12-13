<?php
/*
Script originally written by A.J. Cates

You are free to modify and redistrubte this file.

This message must stay in the file.

If you do decide to use and or edit this file, please email me aj@ajcates.com

If I blog or write an article on this script I may link to you in my blog.
*/
include 'D.php';

class Tumblr {
	var $url; //Our url for tumblr
	var $xml; //The simple XML Object
	var $needed; //Wether or not we need to update our cache
	
	var $json;
	
	var $config = array(
		'num' => 20,
    //Seconds the Script should wait for a timeout from tumblr. 
		'timeout' => 2, 
    //Minutes the cache stays valid for.
		'refresh' => 10, 
    //Wether or not to force a cache refresh. You can override this with a `TumblrForceRefresh=true` url param.
    'forceRefresh' => false, 
		'page' => 1,
		'protocol' => 'http://',
		'username' => 'demo',
		'domain' => 'tumblr.com',
		'url' => '/api/read/json',
		'callback' => 'a',
    'cacheFolder' => '/tumblr/cache/',
    'api_key' => '3kuqJWMaieF5tiaMpZONMJZApkq7QxvV2p8hjIRIqVJx7OlfwB'
	);
	
	static $time;

	public function Tumblr() {
		//$this->xmlLocation = CORE_ROOT. '/plugins/tumblr/tumblr.xml';

		$this->config['cacheFolder'] = PLUGINS_ROOT . $this->config['cacheFolder'];
    if(!empty($_GET['TumblrForceRefresh'])) {
      $this->config['forceRefresh'] = true;
    }
		$this->needed = false;
		self::$time = time();
	}
	
	public function getJson($username, $page=0) {
		$this->config['username'] = $username;
		$this->config['page'] = $page;
		
		$this->json = $this->loadJson($page);
		
		$this->data = json_decode(substr($this->json, strlen($this->config['callback'])+1, -3), true);
		return $this->data;
	}
	
	public function getPostJson($username, $id) {
		$this->config['username'] = $username;
		$this->config['id'] = $id;
		
		$this->json = $this->loadPostJson($id);
		
		$this->data = json_decode(substr($this->json, strlen($this->config['callback'])+1, -3), true);
		return $this->data;
	}
	
	public function getPosts($username, $page=0) {
		$data = $this->getJson($username, $page);
		foreach($data['posts'] as $post) {
      $this->posts[] = array_merge(
        $post,
        $this->getContent($post),
        array(
          'relativeTime' => self::relativeTime($post['date']),
        )
      );
		}
		return $this->posts;
	}
	
	public function getPost($username, $id) {
		$data = $this->getPostJson($username, $id);
		
		foreach($data['posts'] as $post) {
			$this->posts[] = array_merge($post, $this->getContent($post), array('relativeTime' => self::relativeTime($post['date'])));
		}
		return $this->posts;
	}
	
	public function printPost($username, $id) {
		foreach($this->getPost($username, $id) as $post) {
			echo '<div class="tumblr post"><h2>' . $post['title'] . '</h2>' . $post['body'] . '</div>';
		}
		return true;
	}
	
	public function printPosts($username, $page=0) {
		foreach($this->getPosts($username, $page) as $post) {
			echo '<div class="tumblr post"><h2>' . $post['title'] . '</h2>' . $post['body'] . '</div>';
		}
		return true;
	}
	
	public function getInfo($username) {
		$data = $this->getJson($username);
		
		$this->info = $data['tumblelog'];
		
		$this->info['total'] = $data['posts-total'];
		
		return $this->info;
	}
	
	public function getContent($post) {
		$return = array();
		switch($post['type']) {
			case 'regular':
				$return['body'] = $post['regular-body'];
				$return['title'] = $post['regular-title']; 
				break;
			case 'link':
				$return['body'] = '<a href="' . $post['link-url'] . '">' . $post['link-text'] . '</a>';
				$return['title'] = $post['link-text'];
				break;
			case 'quote':
				$return['body'] = $post['quote-text'];
				$return['title'] = 'Quote from ' . $post['quote-source'];
				break;
			case 'photo':
				
				$return['body'] = empty($post['photos']) ?
				'<span class="photo"><img alt="' . strip_tags($post['photo-caption']) . '" src="' . $post['photo-url-500'] . '"/></span>'
				: join(array_map(function($photo) {
					
					return '<span class="photo"><img alt="' . $photo['caption'] . '" src="' . $photo['photo-url-500'] . '"/>' . $photo['caption'] . '</span>';
				}, $post['photos']));
				$return['title'] = strip_tags($post['photo-caption']);
				break;
			case 'conversation':
				$return['body'] = join(array_map(function($v) {
					return '<p><span>' . $v['label'] . '</span>' . $v['phrase'] . '</p>';
				}, $post['conversation']));
				$return['title'] = empty($post['conversation-title']) ? 'Conversation' : $post['conversation-title'];
				break;
			case 'video':
				$return['body'] = $post['video-player'] . $post['video-caption'];
				$return['title'] = substr(strip_tags($post['video-caption']), 0, 140);
				break;
			case 'audio':
				$return['body'] = $post['audio-player'] . $post['audio-caption'];
				$return['title'] = substr(strip_tags($post['audio-caption']), 0, 140);
				break;
			case 'answer':
				$return['body'] = $post['answer'];
				$return['title'] = $post['question'];
				break;
			default:
				$return['body'] = '';
				$return['title'] = '';
				break;
		}
		return $return;
	}
	
	public function refreshNeeded($file) {
		//reuturns true or false depending on if we need to refresh our xml file
    if($this->config['forceRefresh']) {
      return true;
    }
		clearstatcache();
		if(!file_exists($file)) {
			touch($file, self::$time);
			return true;
		}
		if(!$lastMod = filemtime($file)) {
			return true;
		}
		if($lastMod < self::$time - ($this->config['refresh'] * 60)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function cacheJson($cacheFile, $json) {
		//refreshes our xml file, returns true or false depending if succesful
    //return true;

    D::log('cachin json', $cacheFile);
    if(!empty($json) && file_put_contents($cacheFile, $json)) {
      return true;
    } else {
      return false;
    }
	}

	public function loadPostJson($id=0) {
		$cacheFile = $this->config['cacheFolder'] . 'id_' . $id . '.json';
		if($this->refreshNeeded($cacheFile)) {


      //$pid = pcntl_fork();
      //if ($pid == -1) {
           //die('could not fork');
      //} else if ($pid) {
           //// we are the parent
           ////pcntl_wait($status); //Protect against Zombie children
      //} else {
        //// we are the child workin in the BG
        $url = $this->buildPostUrl($id);
        $urlContents = @file_get_contents($url);  
        D::log($url, 'url of loadPostJson being loaded');
        $this->json = @file_get_contents(
          $url,
          false,
          stream_context_create(array('http'=>array(
            'timeout' => $this->config['timeout'],
          )))
        );
        //$this->cacheJson($cacheFile, $this->json);
        //exit('cache updated');
      //}

      if(!empty($urlContents)) {
        $this->json = $urlContents;
        $this->cacheJson($cacheFile, $this->json);
      } else {
        $this->json = file_get_contents($cacheFile);
      }

      //if(empty($this->json)) {
        //D::log($cacheFile, 'Can not get updated json, loading from cache instead.');
        //$this->json = file_get_contents($cacheFile);
      //}

		} else { 
		  $this->json = file_get_contents($cacheFile);
    }
    //D::log('Post json returned from Tumblr->loadPostJson', $this->json);
		return $this->json;
	}


	public function loadJson($page=0) {
		$cacheFile = $this->config['cacheFolder'] . $page . '.json';
		//if(true) {
    if($this->refreshNeeded($cacheFile)) {
      $url = $this->buildUrl($page);
      $urlContents = @file_get_contents($url);  
      if(!empty($urlContents)) {
        $this->json = $urlContents;
        $this->cacheJson($cacheFile, $this->json);
      } else {
        $this->json = file_get_contents($cacheFile);
      }
    } else {
      $this->json = file_get_contents($cacheFile);
    } 

    D::log($cacheFile, 'Cache is still good, loading json from cache.');
    //D::log($this->json, 'json returned from Tumblr->loadJson');
		return $this->json;
	}
	
	static public function relativeTime($date) {
		$time = self::$time - $date;
		if($time < 60) {
			return $date . ' seconds ago';
		} else if($time < 3600) {
			return floor($date/60) . ' minutes ago';
		} else if($time < 86400) {
			return floor(($date/60)/60) . ' hours ago';
		} else if($time < 604800) {
			return floor((($date/60)/60)/24) . ' days ago';
		} else if($time < 2419200) {
			return floor(((($date/60)/60)/24)/7) . ' weeks ago';
		} else {
			return date('F j, Y, g:i a', $time);
		}
	}
	public function buildPostUrl($id=0) {
		return $this->config['protocol'] . $this->config['username'] . '.' . $this->config['domain'] . $this->config['url'] . '?' . http_build_query(array(
			'callback' => $this->config['callback']
		));
	}	
	public function buildUrl($page=0) {
		return $this->config['protocol'] . $this->config['username'] . '.' . $this->config['domain'] . $this->config['url'] . '?' . http_build_query(array(
			'callback' => $this->config['callback'],
			'start' => $this->config['num'] * $page,
			'num' => $this->config['num']
		));
	}
	
}
