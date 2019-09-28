<?php
/**
 * $Project: Pastebin $
 * $Id: db.mysql.class.php,v 1.3 2006/04/27 16:20:06 paul Exp $
 *
 * Pastebin Collaboration Tool
 * http://pastebin.com/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the Affero General Public License
 * Version 1 or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero General Public License for more details.
 *
 * You should have received a copy of the Affero General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
* Database handler
* Very simple, bare bones database handler - if your database isn't supported,
* write another version of this class and change the relevant line of the
* config file to pull it in, i.e. for Postgres support, write a class in
* db.postgres.class.php and set $CONF['dbsystem']='postgres';
*
* All of the SQL used by the rest of the code is contained in here
*/

require_once('lib/pastebin/mysql.class.php');

class DB extends MySQL
{
	var $dblink=null;
	var $dbresult;
	var $cachedir;

	/**
	* Constructor - establishes DB connection
	*/
	function __construct()
	{
		parent::__construct();
		$this->cachedir=$_SERVER['DOCUMENT_ROOT'].'/lib/cache/';

	}


	function gc()
	{
		global $CONF;
		$domain = $CONF['subdomain'];
		$max_posts = $CONF['max_posts'];


		//is there a limit on the number of posts
		if ($max_posts)
		{
			$delete_count=$this->_getPostCount($domain)-$max_posts;
			if ($delete_count>0)
			{
				$this->_trimDomainPosts($domain, $delete_count);
			}
		}

		//delete expired posts
		$this->_deleteExpiredPosts();
		$this->_cacheflush('recent'.$domain);

	}

	/**
	* How many posts on domain?
	* access private
	*/
	function _getPostCount($domain)
	{
		$this->query('select count(*) as cnt from pastebin where domain=?', $domain);
		return $this->next_record() ? $this->f('cnt') : 0;
	}

	/**
	* Delete oldest $deletecount posts from domain
	* access private
	*/
	function _trimDomainPosts($domain, $deletecount)
	{
		//build a one-shot statement to delete old posts
		$sql='delete from pastebin where pid in (';
		$sep='';
		$this->query("select * from pastebin where domain=? order by posted asc limit $deletecount", $domain);
		while ($this->next_record())
		{
			$sql.=$sep.$this->f('pid');
			$sep=',';
		}
		$sql.=')';

		//delete extra posts
		$this->query($sql);
	}

	/**
	* Delete all expired posts
	* access private
	*/
	function _deleteExpiredPosts()
	{
		$this->query("delete from pastebin where expires is not null and now() > expires");
		$this->query("delete from abuse where pid not in (select pid from pastebin) ");
	}

	/**
	* given user specified post id, return a clean version
	*/
	function cleanPostId($raw)
	{
		if (preg_match('/^[hdmf][a-f0-9]{4,8}$/', $raw))
			return $raw;
		else
			return "";
	}

	/**
	* erase a post
	*/
	function deletePost($pid, $domain, $delete_linked=false, $depth=0)
	{
		$this->query('delete from pastebin where pid=?', $pid);
		$this->query('delete from abuse where pid=?', $pid);
		$this->_cacheflush('recent'.$domain);
		return true;
	}

	/**
	* Add post and return id
	* access public
	*/
	function addPost($poster,$domain,$format,$code,$parent_pid,$expiry_flag,$private_flag,$hash,$token)
	{
		$id="";
		//figure out expiry time
		switch ($expiry_flag)
		{
			case 'h';
				$expires="DATE_ADD(NOW(), INTERVAL 1 HOUR)";
				break;
			case 'd';
				$expires="DATE_ADD(NOW(), INTERVAL 1 DAY)";
				break;
			case 'f';
				$expires="NULL";
				break;
			default:
			case 'm';
				$expires="DATE_ADD(NOW(), INTERVAL 1 MONTH)";
				break;


		}

		//try and get a unique filename
		$un=false;
		while (!$un)
		{
			//get a random id
			$id=$expiry_flag.dechex(mt_rand(1,2147483647));
			$this->query("select distinct pid from pastebin where pid=?", $id);
			if ($this->next_record())
				$un=false;
			else
				$un=true;
		}

		$this->query('insert into pastebin (pid, poster, domain, posted, format, code, parent_pid, expires, expiry_flag, private_flag, hash, token, ip) '.
				"values (?, ?, ?, now(), ?, ?, ?, $expires, ?, ?, ?, ?, ?)",
				$id, $poster,$domain,$format,$code,$parent_pid, $expiry_flag, $private_flag, $hash, $token, $_SERVER['REMOTE_ADDR']);
		//$id=$this->get_insert_id();

		//add post to mru list - for small installations, this isn't really necessary
		//but once the pastebin table gets >10,000 entries, things can get pretty slow

		//flush recent list
		$this->_cacheflush('recent'.$domain);

		return $id;
	}

	function _getDupCount($hash, $domain)
	{
		$this->query('select count(*) as cnt from pastebin where hash is not null and hash=? and domain=? and private_flag=?', $hash, $domain, 'n');
		return $this->next_record() ? $this->f('cnt') : 0;
	}

	function isDuplicate($hash, $domain)
	{
		$dup_count=$this->_getDupCount($hash, $domain);
		if ($dup_count>0)
		{
			return true;
		}
		return false;
	}

	function getHashPost($hash, $domain)
	{
		$this->query('select pid '.
				'from pastebin where hash=? and domain=?', $hash, $domain);

		if ($this->next_record())
			$id = $this->f('pid');
		else
			$id = NULL;

		return $id;
	}

	 /**
	* Return entire pastebin row for given id/subdomdain
	* access public
	*/
	function getPost($id, $domain)
	{
		global $is_admin;

		if($is_admin)
		{
			$this->query('select *,date_format(posted, \'%a %D %b %H:%i\') as postdate '.
				'from pastebin where pid=?', $id);
		}else {
			$this->query('select *,date_format(posted, \'%a %D %b %H:%i\') as postdate '.
				'from pastebin where pid=? and domain=?', $id, $domain);
		}

		if ($this->next_record())
			return $this->row;
		else
			return false;

	}

	/**
	* Return summaries for $count posts ($count=0 means all)
	* access public
	*/
	function getRecentPostSummary($domain, $count)
	{
		global $is_admin;
		global $CONF;

		if (strlen($domain))
			return $this->searchRecentPostSummary($domain, $count);

		$limit=$count?"limit $count":"";

		$posts=array();

		$cacheid="recent".$domain;

		if($is_admin) {
			$posts=$this->_cachedquery(false, $cacheid, "select p.pid,p.poster,unix_timestamp()-unix_timestamp(p.posted) as age, ".
				"date_format(p.posted, '%a %D %b %H:%i') as postdate ".
				"from pastebin as p ".
				"order by p.posted desc, p.pid desc $limit");
		}else{
			$posts=$this->_cachedquery($CONF['cache_recent'], $cacheid, "select p.pid,p.poster,unix_timestamp()-unix_timestamp(p.posted) as age, ".
				"date_format(p.posted, '%a %D %b %H:%i') as postdate ".
				"from pastebin as p ".
				"where p.domain=? and p.private_flag=? ".
				"order by p.posted desc, p.pid desc $limit", $domain, 'n');
		}

		return $posts;
	}

	function searchRecentPostSummary($domain, $count)
	{
		global $is_admin;

		$limit=$count?"limit $count":"";

		$posts=array();
		if($is_admin){
			$this->query("select pid,poster,unix_timestamp()-unix_timestamp(posted) as age, ".
				"date_format(posted, '%a %D %b %H:%i') as postdate ".
				"from pastebin ".
				"order by posted desc, pid desc $limit");
		}else{
			$this->query("select pid,poster,unix_timestamp()-unix_timestamp(posted) as age, ".
				"date_format(posted, '%a %D %b %H:%i') as postdate ".
				"from pastebin ".
				"where domain=? and private_flag=? ".
				"order by posted desc, pid desc $limit", $domain, 'n');
		}
		while ($this->next_record())
		{
			$posts[]=$this->row;
		}

		return $posts;
	}



	/**
	* Get follow up posts for a particular post
	* access public
	*/
	function getFollowupPosts($pid, $limit=5)
	{
		//any amendments?
		$childposts=array();
		$this->query("select pid,poster,".
			"date_format(posted, '%a %D %b %H:%i') as postfmt ".
			"from pastebin where parent_pid=? ".
			"order by posted limit $limit", $pid);
		while ($this->next_record())
		{
			$childposts[]=$this->row;
		}

		return $childposts;

	}

	/**
	* Save formatted code for a post
	* access public
	*/
	function saveFormatting($pid, $codefmt, $codecss)
	{
		$this->query("update pastebin set codefmt=?,codecss=? where pid=?",
			$codefmt, $codecss, $pid);
	}

	function _cacheflush($cacheid)
	{
		$cachefile=$this->cachedir.$cacheid;
		if (file_exists($cachefile))
		{
			unlink($cachefile);
		}
	}

	function _cachedquery($docache, $cacheid, $sql)
	{
		$cachefile=$this->cachedir.$cacheid;

		if ($docache)
		{
			if (file_exists($cachefile))
			{
				$serialized=@file_get_contents($cachefile);
				if (strlen($serialized))
				{
					return unserialize($serialized);
				}
			}
		}

		if (is_null($this->dblink))
			$this->_connect();

		//cache miss
		//been passed more parameters? do some smart replacement
		if (func_num_args() > 3)
		{
			//query contains ? placeholders, but it's possible the
			//replacement string have ? in too, so we replace them in
			//our sql with something more unique
			$q=md5(uniqid(rand(), true));
			$sql=str_replace('?', $q, $sql);

			$args=func_get_args();
			for ($i=3; $i<count($args); $i++)
			{
				$sql=preg_replace("/$q/", "'".preg_quote(mysqli_real_escape_string($this->dblink, $args[$i]))."'", $sql,1);

			}

			//we shouldn't have any $q left, but it will help debugging if we change them back!
			$sql=str_replace($q, '?', $sql);
		}


		$result=array();


		$this->dbresult=mysqli_query($this->dblink, $sql);
		if ($this->dbresult)
		{
			while($row=mysqli_fetch_array($this->dbresult,MYSQLI_ASSOC))
			{
				$result[]=$row;
			}
		}



		if ($docache)
		{
			//we have our result
			$serialized=serialize($result);

			//try and get a lock
			$lock = $cachefile.'.lock';
			$lf = @fopen ($lock, 'x');
			$i=0;
			while (($lf === FALSE) && ($i++ < 20))
			{
				clearstatcache();
				usleep(rand(5,85));
				$lf = @fopen ($lock, 'x');
			}

			//did we get the lock?
			if ($lf !== FALSE) {
				$fp = fopen($cachefile, 'w');
					fwrite( $fp, $serialized);
				fclose( $fp);

				//unlock
				fclose($lf);
				unlink($lock);
			}
		}

		return $result;

	}

	function addAbusePost($pid,$domain,$msg)
	{
		$this->query('insert into abuse (pid, domain, msg) '.
				"values (?, ?, ?)",
				$pid,$domain,$msg);
	}

	function getAbusePostSummary($domain)
	{
		global $is_admin;

		$posts=array();

		if (!$is_admin)
			return $posts;

		$posts=array();
		$this->query("select distinct pid ".
			"from abuse ".
			"where domain=? ".
			"order by pid desc", $domain);
		while ($this->next_record())
		{
			$posts[]=$this->row;
		}

		return $posts;
	}


	function getAbusePost($id, $domain)
	{
		global $is_admin;

		if (!$is_admin)
			return false;

		$abuse='';
		$hasabuse=false;
		$this->query('select msg '.
			'from abuse where pid=? and domain=?', $id, $domain);
		while($this->next_record())
		{
			$hasabuse=true;
			//$abuse=$abuse . implode(' ',$this->f('msg'));
			$abuse=$abuse . $this->f('msg');
		}

		if($hasabuse)
			return $abuse;
		else
			return false;
	}

	/**
	* The class uses this internally to find out the current time
	* For implementing historical loading of the db, you can use this to
	* set a "fake" now time...
	* access public
	*/
	function now($override=0)
	{
		if ($override>0)
			$this->now=$override;

		if (isset($this->now))
			return $this->now;
		else
			return time();
	}

	function createdb()
	{
		/*

		ALTER DATABASE `pastebindb` COLLATE utf8_general_ci;

		CREATE TABLE `pastebin` (
			`pid` varchar(11) NOT NULL,
			`domain` varchar(255) default '',
			`poster` varchar(16) default NULL,
			`posted` datetime default NULL,
			`parent_pid` varchar(11) NOT NULL default '',
			`expiry_flag` ENUM('h', 'd','m', 'f') NOT NULL DEFAULT 'm',
			`private_flag` ENUM('y', 'n') NOT NULL DEFAULT 'n',
			`expires` DATETIME,
			`format` varchar(16) default NULL,
			`code` text,
			`codefmt` mediumtext,
			`codecss` text,
			`hash` varchar(128) default NULL,
			`token` varchar(32) default NULL,
			`ip` varchar(15) default NULL,

			PRIMARY KEY  (`pid`),
			KEY `domain` (`domain`),
			KEY `parent_pid` (`parent_pid`),
			KEY `expires` (`expires`)
		);

		CREATE TABLE `abuse`
		(
			`pid` varchar(11) NOT NULL,
			`domain` varchar(255) default '',
			`msg` text
		);

		*/
	}
}
?>
