<?php

require_once('lib/pastebin/pastebin.class.php');

/**
* Placeholder for your own spam rules
*/
class SpamFilter
{
	public function canPost($post, $pb)
	{
		if($pb->isDuplicate($post['hash'], $post['domain']))
		{
			return false;
		}

		return true;
	}

}
