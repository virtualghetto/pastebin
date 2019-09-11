<?php

require_once('lib/pastebin/pastebin.class.php');

/**
* Placeholder for your own spam rules
*/
class SpamFilter
{
	public function canPost($post, $pb)
	{
		$parent_pid='';
		if (isset($post['parent_pid']) && strlen($post['parent_pid']))
		{
			$parent_pid=$pb->cleanPostId($post['parent_pid']);
			$parent_post=$pb->getPost($parent_pid);
			if(strcmp($parent_post['code'], $post['code2']) == 0)
				return false;
		}
		return true;
	}

}
