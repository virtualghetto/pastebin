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
* File-based database handler
*
* Instead of using a DB, this uses the file system to store posts. These are
* stored in a directory structure

posts/[hdmf]/ab/cd/ef/abcdefgh

the top level d, m or f directly allows us to identify the longevity of the
post to allow periodic garbage collection.

The only remaining trick is the list of recent posts for each domain

for this we serialise an array of all the pertinent data and save in a domain specific
files e.g.

recent/b/a/n/banjo

we can expire these on an as needed basis



*/


class DB
{
	var $dblink=null;
	var $dbresult;
	var $postdir;

	/**
	* Constructor - establishes DB connection
	*/
	function __construct()
	{

		$this->postdir=$_SERVER['DOCUMENT_ROOT'].'/lib/posts/';
		if (!is_writable($this->postdir)) die("{$this->postdir} needs to be a writable dir to use file storage engine");


	}

	/**
	* Garbage collector - called at intervals to perform clean up
	* access public
	*/
	function gc()
	{
		global $CONF;

		$this->_cleanMRU($CONF['subdomain']);

	}

	/**
	* Turn post id to filename creating any dirs as necessary
	*/
	function _idToPath($id, $ensure_dirs=true)
	{
		//build directory and filename
		//format is f/aa/bb/cc/faabbccdd
		$dir=$this->postdir.substr($id,0,1); if ($ensure_dirs && !is_dir($dir)) mkdir($dir);
		//$dir.='/'.substr($id,1,2); if ($ensure_dirs && !is_dir($dir)) mkdir($dir);
		//$dir.='/'.substr($id,3,2); if ($ensure_dirs && !is_dir($dir)) mkdir($dir);
		//$dir.='/'.substr($id,5,2); if ($ensure_dirs && !is_dir($dir)) mkdir($dir);
		$file=$dir.'/'.$id;

		return $file;
	}

	/**
	* turn domain to pathname for MRU file
	*/
	function _domainToPath($domain, $ensure_dirs=true)
	{
		$dir=$this->postdir.'/mru';
		if ($ensure_dirs && !is_dir($dir)) mkdir($dir);

		/*
		$l=strlen($domain);
		if ($l)
		{
			//break the domain into subdirs
			//for ($p=0; $p<min(3,$l); $p++)
			//{
			//	$dir.='/'.substr($domain,$p,1); if ($ensure_dirs && !is_dir($dir)) mkdir($dir);
			//}
			$file=$dir."/{$domain}.mru";
		}
		else
		{
			$file=$dir.'/default.mru';
		}
		*/
		$file=$dir.'/default.mru';

		return $file;
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

	/**
	* Add post and return id
	* access public
	*/
	function addPost($poster,$domain,$format,$code,$parent_pid,$expiry_flag,$private_flag,$hash,$token)
	{
		//figure out expiry time
		switch ($expiry_flag)
		{
			case 'h';
				$expires=$this->now()+3600;
				break;
			case 'd';
				$expires=$this->now()+86400;
				break;
			case 'f';
				$expires=0;
				break;
			default:
			case 'm';
				$expiry_flag="m";
				$expires=$this->now()+86400*30;
				break;


		}

		$post=array();
		$post['posted']=$this->now();
		$post['expires']=$expires;
		$post['poster']=$poster;
		$post['domain']=$domain;
		$post['format']=$format;
		$post['code']=$code;
		$post['parent_pid']=$parent_pid;
		$post['expiry_flag']=$expiry_flag;
		$post['private_flag']=$private_flag;
		$post['hash']=$hash;
		$post['token']=$token;
		$post['followups']=array();
		$post['ip']=$_SERVER['REMOTE_ADDR'];



		//try and get a unique filename
		$fp=false;
		while (!$fp)
		{
			//get a random id
			$id=$expiry_flag.dechex(mt_rand(1,2147483647));

			$file=$this->_idToPath($id);
			$fp=@fopen ($file, 'x');

		}

		if ($fp)
		{
			fwrite($fp, serialize($post));
			fclose($fp);


			//add a reference to this post in the parent post
			if ($post['parent_pid'])
			{
				$followup=array();
				$followup['pid']=$id;
				$followup['poster']=$post['poster'];
				$followup['postfmt']=strftime('%a %d %b %H:%M', $post['posted']);

				$file=$this->_idToPath($post['parent_pid'], false);

				$fp=@fopen ($file, 'r');
				if ($fp)
				{
					//get a lock
					flock($fp, LOCK_EX);

					//read and update post
					$parent=$this->getPost($post['parent_pid'], $domain);
					$parent['followups'][]=$followup;

					$fp2=@fopen ($file, 'w');
					fwrite($fp2, serialize($parent));
					fclose($fp2);

					flock($fp, LOCK_UN);
					fclose($fp);

					//touch the file with what we believe is current time
					//we do this for the legacy loading
					touch($file,$this->now());


				}

			}

		}

		//add to domain specific mru
		$mruentry=array();
		$mruentry['pid']=$id;
		$mruentry['posted']=$post['posted'];
		$mruentry['expires']=$post['expires'];
		$mruentry['private']=$post['private_flag'];
		$mruentry['poster']=$post['poster'];
		$mruentry['hash']=$post['hash'];
		$mruentry['domain']=$post['domain'];
		//$mruentry['postdate']=strftime('%a %d %b %H:%M', $post['posted']);

		$mrufile=$this->_domainToPath($domain);

		//$this->_cleanMRU($domain);

		//get a lock on the file before attempting anything...
		$fp=fopen($mrufile, 'a+');
		if ($fp)
		{
			if (flock($fp, LOCK_EX))
			{
				//read existing mru list if present...
				fseek($fp, 0);
				$fstat = fstat($fp);
				if ($fstat['size'])
				{
					$data=fread($fp,$fstat['size']);
					$mru=unserialize($data);

					//lets lose anything which is expired - we could do this when
					//reading the MRU, but more efficient to do it here
					foreach($mru as $idx=>$entry)
					{
						$mrupost=$this->_idToPath($entry['pid'], false);
						if (!file_exists($mrupost))
							unset($mru[$idx]);
					}

					//trim the list
					//if (count($mru)>15)
					//	array_pop($mru);
				}
				else
				{
					$mru=array();
				}

				//add our entry
				array_unshift($mru, $mruentry);

				//write it back out
				$fp2=fopen($mrufile, 'w');
				fwrite($fp2, serialize($mru));
				fclose($fp2);


				//release lock and close
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
		else
		{
			echo "Could not open $mrufile<br>";
		}

		return $id;
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

	function _cleanMRU($domain, $deleteid="")
	{
		$mrufile=$this->_domainToPath($domain);

		//get a lock on the file before attempting anything...
		$fp=@fopen($mrufile, 'r');
		if ($fp)
		{
			if (flock($fp, LOCK_EX))
			{
				//read existing mru list if present...
				$fstat = fstat($fp);
				if ($fstat['size'])
				{
					$data=fread($fp,$fstat['size']);
					$mru=unserialize($data);

					foreach($mru as $idx=>$entry)
					{
						if ($entry['pid']==$deleteid)
						{
							//its the entry we want to delete
							unset($mru[$idx]);
						}
						else
						{
							$file=$this->_idToPath($entry['pid'], false);
							$ok=file_exists($file);
							if ($ok)
							{
								//is the file too old to live?
								$age=time()-filemtime($file);
								$expired=false;
								switch (substr($entry['pid'], 0,1))
								{
									case 'h';
										$expired=$age>3600;
										break;
									case 'd':
										$expired=$age>86400;
										break;
									case 'm':
										$expired=$age>(86400*30);
										break;
									case 'f':
									default:
										$expired=false;
										break;

								}

								if ($entry['expires'] && ($this->now() > $entry['expires']))
								{
									$expired=true;
								}

								if ($expired)
								{
									//$expired=$age>86400;
									unlink($file);
									unset($mru[$idx]);
								}
							}
							else
							{
								//remove from MRU
								unset($mru[$idx]);
							}
						}

					}


					//write it back out
					$fp2=fopen($mrufile, 'w');
					fwrite($fp2, serialize($mru));
					fclose($fp2);
				}

				//release lock and close
				flock($fp, LOCK_UN);
				fclose($fp);
			}
		}
	}


	 /**
	* erase a post
	*/
	function deletePost($pid, $domain, $delete_linked=false, $depth=0)
	{
		$file=$this->_idToPath($pid, false);
		$ok=file_exists($file);
		if ($ok)
		{
			$post=unserialize(file_get_contents($file));

			if ($delete_linked && is_array($post['followups']))
			{
				foreach($post['followups'] as $idx=>$followup)
				{
					$this->deletePost($followup['pid'], $domain, true, $depth+1);
				}
			}

			//delete it
			unlink($file);

			//update mru too?
			if ($depth==0)
			   $this->_cleanMRU($post['domain'], $pid);
		}

		return $ok;
	}

	function isDuplicate($hash, $domain)
	{
		$mrufile=$this->_domainToPath($domain);
		if (file_exists($mrufile))
		{
			$mru=unserialize(file_get_contents($mrufile));
			$smru = array();

			foreach($mru as $idx=>$entry)
			{
				if ($entry['hash'] == $hash && $entry['domain'] == $domain && $entry['private'] == 'n')
					return true;

			}
		}

		return false;
	}

	function getHashPost($hash, $domain)
	{
		$mrufile=$this->_domainToPath($domain);
		$id = NULL;
		if (file_exists($mrufile))
		{
			$mru=unserialize(file_get_contents($mrufile));
			$smru = array();

			foreach($mru as $idx=>$entry)
			{
				if ($entry['hash'] == $hash && $entry['domain'] == $domain)
				{
					$id = $entry['pid'];
					break;
				}

			}
		}

		return $id;
	}

	 /**
	* Return entire pastebin row for given id/subdomdain
	* access public
	*/
	function getPost($id, $domain)
	{
		global $is_admin;

		$file=$this->_idToPath($id, false);

		$rec=false;
		if (is_file($file))
		{
			$rec=unserialize(file_get_contents($file));

			$rec['modified']=filemtime($file);
			$rec['postdate'] = strftime('%a %e %b %H:%M', $rec['posted']);

			//check domain - only an admin can view a post on the
			//'wrong' domain
			if (!$is_admin && ($rec['domain']!=$domain))
			{
				$rec=false;
			}

			//check expiry
			if ($rec['expires'] && ($this->now() > $rec['expires']))
			{
				$rec=false;
				//$this->deletePost($id, $domain, false);
			}


		}

		//echo "<pre>";
		//	var_dump($rec);
		//echo "</pre>";

		return $rec;

	}

	 /**
	* Return summaries for $count posts ($count=0 means all)
	* access public
	*/
	function getRecentPostSummary($domain, $count)
	{
		global $is_admin;

		$mrufile=$this->_domainToPath($domain);
		if (file_exists($mrufile))
		{
			$mru=unserialize(file_get_contents($mrufile));
			$smru = array();

			//add age
			$now=$this->now();
			foreach($mru as $idx=>$entry)
			{
				if (!$is_admin && ($entry['expires'] && ($now > $entry['expires'])))
					continue;

				if (!$is_admin && ($entry['private']!='n'))
					continue;

				if (!$is_admin && ($mru[$idx]['domain']!=$domain))
					continue;

				$mru[$idx]['age']=$now-$entry['posted'];
				$mru[$idx]['postdate']=strftime('%a %d %b %H:%M', $entry['posted']);

				array_push($smru,$mru[$idx]);
			}

			$mru = $smru;

			if($count > 0) {
				while (count($mru)>$count)
					array_pop($mru);
			}

		}
		else
		{
			$mru=array();
		}

		return $mru;
	}





	/**
	* Get follow up posts for a particular post
	* access public
	*/
	function getFollowupPosts($pid, $limit=5)
	{
		//there should not be any need to call this, as
		//we give details of childposts with a regular get
		//die("getFollowupPosts not required for file storage engine - ensure code performs a check before calling this!");
		return array();
	}

	/**
	* Save formatted code for a post
	* access public
	*/
	function saveFormatting($id, $codefmt, $codecss)
	{
		$dir=$this->postdir.substr($id,0,1);
		//$dir.='/'.substr($id,1,2);
		//$dir.='/'.substr($id,3,2);
		//$dir.='/'.substr($id,5,2);
		$file=$dir.'/'.$id;

		$rec=false;
		if (file_exists($file))
		{
			$rec=unserialize(file_get_contents($file));
			$rec['codefmt']=$codefmt;
			$rec['codecss']=$codecss;

			$fp=@fopen($file, 'w');
			if ($fp)
			{
				fwrite($fp,serialize($rec));
				fclose($fp);
			}
		}
	}






	static function dumpDiagnostics()
	{
		/*
		global $CONF;
		if ($CONF["maintainer_mode"])
		{
			global $_queries;
			echo "<hr>";
			foreach($_queries as $q)
			{
				echo "<pre><code>\n".htmlentities($q['sql'])."\n</code></pre>\n";
				echo "<b>".$q['time']."</b><hr>\n\n\n";
			}
		}
		*/
	}



	/**
	* get last error
	* @access public
	*/
	function get_db_error()
	{
		return "";
	}

	function addAbusePost($pid,$domain,$msg)
	{
		//if(strlen(trim($domain)))
		//	$domain = '.' . $domain;

                //new method...write info file to abuse folder
                //$file=$_SERVER['DOCUMENT_ROOT']."/lib/abuse/$pid".$domain;
                $file=$_SERVER['DOCUMENT_ROOT']."/lib/abuse/$pid";
                $fp=fopen($file, 'a+');
                fwrite($fp, $msg);
                fclose($fp);
	}

	function getAbusePostSummary($domain)
	{

		global $is_admin;

		$abuse=array();

		if (!$is_admin)
			return $abuse;

		$dir=$_SERVER['DOCUMENT_ROOT'].'/lib/abuse/';
		$d=dir($dir);
		while (false !== ($entry = $d->read()))
		{
			if ($entry[0]!='.')
			{
				//$ex = explode('.', $entry);
				//$pid= array_shift($ex);
				//$sb = implode('.', $ex);
				$pid=$entry;
				//does post exist?
				$file=$_SERVER['DOCUMENT_ROOT'].'/lib/posts/'.substr($pid,0,1);
				//$file.='/'.substr($pid,1,2);
				//$file.='/'.substr($pid,3,2);
				//$file.='/'.substr($pid,5,2);
				$file.='/'.$pid;

				if (file_exists($file))
				{
					//if (strcmp($domain, $sb))
					//	continue;

					$abuseentry=array();
					$abuseentry['pid'] = $pid;
					array_unshift($abuse, $abuseentry);
				}
				else
				{
					unlink($dir.$entry);
				}

			}
		}

		$d->close();
		return $abuse;
	}

	function getAbusePost($id, $domain)
	{
		global $is_admin;

		$abuse=false;

		if (!$is_admin)
			return $abuse;

		//if(strlen(trim($domain)))
		//	$domain = '.' . $domain;

		//$abusefile=$_SERVER['DOCUMENT_ROOT'].'/lib/abuse/'. $id . $domain;
		$abusefile=$_SERVER['DOCUMENT_ROOT'].'/lib/abuse/'. $id;
		if (file_exists($abusefile))
		{
			$abuse=file_get_contents($abusefile);
		}

		return $abuse;
	}

}
?>
