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


		//is there a limit on the number of posts
		if ($CONF['max_posts'])
		{
			$delete_count=$this->_getPostCount($CONF['subdomain'])-$CONF['max_posts'];
			if ($delete_count>0)
			{
				$this->_trimDomainPosts($CONF['subdomain'], $delete_count);
			}
		}

		//delete expired posts
		$this->_deleteExpiredPosts();

	}

	/**
	* How many posts on domain $subdomain?
	* access private
	*/
	function _getPostCount($subdomain)
	{
		$this->query('select count(*) as cnt from pastebin where domain=?', $subdomain);
		return $this->next_record() ? $this->f('cnt') : 0;
	}

	/**
	* Delete oldest $deletecount posts from $subdomain
	* access private
	*/
	function _trimDomainPosts($subdomain, $deletecount)
	{
		//build a one-shot statement to delete old posts
		$sql='delete from pastebin where pid in (';
		$sep='';
		$this->query("select * from pastebin where domain=? order by posted asc limit $deletecount", $subdomain);
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
		$this->query("delete from recent where pid not in (select pid from pastebin) ");
	}

	/**
	* given user specified post id, return a clean version
	*/
	function cleanPostId($raw)
	{
		return intval($raw);
	}

	/**
	* erase a post
	*/
	function deletePost($pid, $delete_linked=false, $depth=0)
	{
		$this->query('delete from pastebin where pid=?', $pid);
		return true;
	}

	/**
	* Add post and return id
	* access public
	*/
	function addPost($poster,$subdomain,$format,$code,$parent_pid,$expiry_flag,$token)
	{
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
			default:
			case 'm';
				$expires="DATE_ADD(NOW(), INTERVAL 1 MONTH)";
				break;


		}


		$this->query('insert into pastebin (poster, domain, posted, format, code, parent_pid, expires,expiry_flag, ip) '.
				"values (?, ?, now(), ?, ?, ?, $expires, ?, ?)",
				$poster,$subdomain,$format,$code,$parent_pid, $expiry_flag, $_SERVER['REMOTE_ADDR']);
		$id=$this->get_insert_id();

		//add post to mru list - for small installations, this isn't really necessary
		//but once the pastebin table gets >10,000 entries, things can get pretty slow
		$this->query('lock tables recent write');
		$this->query('update recent set seq_no=seq_no+1 where domain=? order by seq_no desc', $subdomain);
		$this->query('insert into recent (domain,seq_no,pid) values (?,1,?)', $subdomain, $id);
		$this->query('delete from recent where domain=? and seq_no=11', $subdomain);
		$this->query('unlock tables');

		//flush recent list
		$this->_cacheflush('recent'.$domain);

		return $id;
	}

	 /**
	* Return entire pastebin row for given id/subdomdain
	* access public
	*/
	function getPost($id, $subdomain)
	{
		$this->query('select *,date_format(posted, \'%a %D %b %H:%i\') as postdate '.
			'from pastebin where pid=? and domain=?', $id, $subdomain);
		if ($this->next_record())
			return $this->row;
		else
			return false;

	}

	/**
	* Return summaries for $count posts ($count=0 means all)
	* access public
	*/
	function getRecentPostSummary($subdomain, $count)
	{
		if (strlen($subdomain))
			return $this->searchRecentPostSummary($subdomain, $count);

		$limit=$count?"limit $count":"";

		$posts=array();

		$cacheid="recent".$subdomain;

		$posts=$this->_cachedquery($cacheid, "select p.pid,p.poster,unix_timestamp()-unix_timestamp(p.posted) as age, ".
			"date_format(p.posted, '%a %D %b %H:%i') as postdate ".
			"from pastebin as p ".
			"inner join recent as r on (r.domain=? and p.pid=r.pid) ".
			"order by p.posted desc, p.pid desc $limit", $subdomain);

		return $posts;
	}

	function searchRecentPostSummary($subdomain, $count)
	{
		$limit=$count?"limit $count":"";

		$posts=array();
		$this->query("select pid,poster,unix_timestamp()-unix_timestamp(posted) as age, ".
			"date_format(posted, '%a %D %b %H:%i') as postdate ".
			"from pastebin ".
			"where domain=? ".
			"order by posted desc, pid desc $limit", $subdomain);
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

	function _cachedquery($cacheid, $sql)
	{
		$cachefile=$this->cachedir.$cacheid;
		if (file_exists($cachefile))
		{
			$serialized=@file_get_contents($cachefile);
			if (strlen($serialized))
			{
				return unserialize($serialized);
			}
		}

		if (is_null($this->dblink))
			$this->_connect();

		//cache miss
		//been passed more parameters? do some smart replacement
		if (func_num_args() > 2)
		{
			//query contains ? placeholders, but it's possible the
			//replacement string have ? in too, so we replace them in
			//our sql with something more unique
			$q=md5(uniqid(rand(), true));
			$sql=str_replace('?', $q, $sql);

			$args=func_get_args();
			for ($i=2; $i<=count($args); $i++)
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



		//we have our result
		$serialized=serialize($result);

		//try and get a lock
		$lock = $cachefile.'.lock';
		$lf = @fopen ($lock, 'x');
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

		return $result;

	}

	function addAbusePost($pid,$subdomain,$msg)
	{
		$this->query('insert into abuse (pid, domain, msg) '.
				"values (?, ?, ?)",
				$pid,$subdomain,$msg);
	}

	function getAbusePostSummary($subdomain)
	{
		global $is_admin;

		$posts=array();

		if (!$is_admin)
			return $posts;

		$posts=array();
		$this->query("select distinct pid ".
			"from abuse ".
			"where domain=? ".
			"order by pid desc", $subdomain);
		while ($this->next_record())
		{
			$posts[]=$this->row;
		}

		return $posts;
	}


	function getAbusePost($id, $subdomain)
	{
		global $is_admin;

		if (!$is_admin)
			return false;

		$abuse='';
		$hasabuse=false;
		$this->query('select msg '.
			'from abuse where pid=? and domain=?', $id, $subdomain);
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
			`pid` int(11) NOT NULL auto_increment,
			`domain` varchar(255) default '',
			`poster` varchar(16) default NULL,
			`posted` datetime default NULL,
			`parent_pid` int(11) default '0',
			`expiry_flag` ENUM('h', 'd','m', 'f') NOT NULL DEFAULT 'm',
			`expires` DATETIME,
			`format` varchar(16) default NULL,
			`code` text,
			`codefmt` mediumtext,
			`codecss` text,
			`ip` varchar(15) default NULL,

			PRIMARY KEY  (`pid`),
			KEY `domain` (`domain`),
			KEY `parent_pid` (`parent_pid`),
			KEY `expires` (`expires`)
		);

		create table recent
		(
			pid int not null,
			domain varchar(255),
			seq_no int not null,

			primary key(domain,seq_no)
		);

		CREATE TABLE `abuse`
		(
			`pid` int(11) NOT NULL,
			`domain` varchar(255) default '',
			`msg` text
		);
		*/
	}
}
?>
