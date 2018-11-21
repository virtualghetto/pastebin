<?php
/**
 * $Project: Pastebin $
 * $Id: layout.php,v 1.1 2006/04/27 16:22:39 paul Exp $
 *
 * Pastebin Collaboration Tool
 * http://pastebin.com/
 *
 * This file copyright (C) 2006 Paul Dixon (paul@elphin.com)
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

echo "<?xml version=\"1.0\" encoding=\"".$charset_code[$charset]['http']."\"?>\n";

if (!isset($pid)) {
	$pid='';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--
pastebin.com Copyright 2006 Paul Dixon - email suggestions to lordelph at gmail.com
-->
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo $page['title'] ?></title>
<meta name="ROBOTS" content="NOARCHIVE"/>
<link rel="stylesheet" type="text/css" media="screen" href="/pastebin.css?ver=6" />

<?php if (isset($page['post']['codecss']))
{
	echo '<style type="text/css">';
	echo $page['post']['codecss'];
	echo '</style>';
}
?>
</head>


<body>
<div style="display:none;">
<h1 style="display: none;">pastebin - collaborative debugging</h1>
<p style="display: none;">pastebin is a collaborative debugging tool allowing you to share
and modify code snippets while chatting on IRC, IM or a message board.</p>
<p style="display: none;">This site is developed to XHTML and CSS2 W3C standards.
If you see this paragraph, your browser does not support those standards and you
need to upgrade.  Visit <a href="http://www.webstandards.org/upgrade/" target="_blank">WaSP</a>
for a variety of options.</p>
</div>

<div id="titlebar"><?php
	echo "<a rel=\"nofollow\" href=\"{$CONF['this_script']}\">{$page['title']}</a>";
	/*
	if ($subdomain=='')
	{
		echo " <a href=\"{$CONF['this_script']}?help=1\">View Help</a>";
	}
	else
	{
		echo " <a href=\"{$CONF['this_script']}?help=1\">What's a private pastebin?</a>";
	}
	*/
?>
</div>



<?php echo '<div id="menu">';
if ($is_admin){

	//Abuse block
        $count=0;
	$bullets="";

	foreach($page['abuse'] as $idx=>$entry)
	{
		$bullets.= '<li><a href="'.$entry['url'].'">'.$entry['pid'].'</a></li>';
		$count++;
	}
	echo '<h1>'.t('Abuse').' ('.$count.')</h1><ul>';
        echo $bullets;

	if ($count==0)
		echo '<li>no abuse reports</li>';
        echo '</ul>';
	echo '<h1>'.t('Cron').'</h1>';
	echo '<p>' . t('Run ') . "<a href=\"{$CONF['this_script']}?cron=1\">" . t('cron') . '</a>' . t(' cleanup job.') . '</p>';
}


	//About block
	echo '<h1>'.t('About').'</h1>';
	echo '<p>' . t('See ') . "<a href=\"{$CONF['this_script']}?help=1\">" . t('help') . '</a>' . t(' for details.') . '</p>';
	//echo '<p>' . t('Pastebin is a tool for collaborative debugging or editing,');
	//echo " <a href=\"{$CONF['this_script']}?help=1\">" . t('See help for details') . '</a>.</p>';

	echo '<ul>';
	echo "<li><a rel=\"nofollow\" href=\"{$CONF['this_script']}\">".t('Make a new post').'</a></li>';
	echo '</ul>';

	//Encryption help block
	//echo '<h1>'.t('Encryption').'</h1>';
	//echo '<p>' . t('Use \'gpg -ac\' to encrypt and \'gpg -d\' to decrypt.') . '</p>';

	//Recent posts block
	echo '<h1>'.t('Recent Posts').'</h1>';

	echo '<ul>';
	foreach($page['recent'] as $idx=>$entry)
	{
		if (isset($entry['pid']) && isset($pid) && ($entry['pid']==$pid))
			$cls=" class=\"highlight\"";
		else
			$cls="";

		echo "<li{$cls}><a href=\"{$entry['url']}\">";
		echo $entry['poster'];
		echo "</a><br/>{$entry['agefmt']}</li>\n";
	}

	echo '</ul>';


echo '</div>';
?>


<div id="content">

<?php

///////////////////////////////////////////////////////////////////////////////
// show processing errors
//
if (!empty($pastebin->errors))
{
	echo '<h1>'.t('Errors').'</h1><ul>';
	foreach($pastebin->errors as $err)
	{
		echo "<li>$err</li>";
	}
	echo "</ul>";
	echo "<hr />";
}

if (!empty($page['delete_message']))
{
	echo "<h1>{$page['delete_message']}</h1><br/>";
}

if (isset($_REQUEST["diff"]))
{

	$newpid=$pastebin->cleanPostId($_REQUEST['diff']);

	$newpost=$pastebin->getPost($newpid);
	if (isset($newpost['parent_pid']) && $newpost['parent_pid']!='0')
	{
		$oldpost=$pastebin->getPost($newpost['parent_pid']);
		if (isset($oldpost['pid']))
		{
			$page['pid']=$newpid;
			$page['current_format']=$newpost['format'];
			$page['editcode']=$newpost['code'];
			$page['posttitle']='';

			//echo "<div style=\"text-align:center;border:1px red solid;padding:5px;margin-bottom:5px;\">Diff feature is in BETA! If you have feedback, send it to lordelph at gmail.com</div>";

			echo "<h1>";
			printf(t('Difference between<br/>modified post %s by %s on %s and<br/>'.
				'original post %s by %s on %s'),
				"<a href=\"".$pastebin->getPostUrl($newpost['pid'])."\">{$newpost['pid']}</a>",
				$newpost['poster'],
				$newpost['postdate'],
				'<a href="'.$pastebin->getPostUrl($oldpost['pid'])."\">{$oldpost['pid']}</a>",
				$oldpost['poster'],
				$oldpost['postdate']);

			echo "<br/>";

			echo "</h1>";

			$newpost['code']=preg_replace('/^'.$CONF['highlight_prefix'].'/m', '', $newpost['code']);
			$oldpost['code']=preg_replace('/^'.$CONF['highlight_prefix'].'/m', '', $oldpost['code']);

			$a1=explode("\n", $newpost['code']);
			$a2=explode("\n", $oldpost['code']);

			$diff=new Diff($a2,$a1, 1);

			echo "<table cellpadding=\"0\" cellspacing=\"0\" class=\"diff\">";
			echo "<tr><td></td><td></td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td></td></tr>";
			echo $diff->output;
			echo "</table>";
		}

	}


}

///////////////////////////////////////////////////////////////////////////////
// show a post
//

if (isset($_GET['help']))
	$page['posttitle']="";

if (!empty($page['post']['posttitle']))
{
		echo "<h1>{$page['post']['posttitle']}";
		if (strlen($page['post']['parent_pid']))
		{
			if (isset($page['post']['parent_url']) && isset($page['post']['parent_poster']) && isset($page['post']['parent_diffurl'])){
			echo ' (';
			printf(t("modification of post by %s"),
				"<a href=\"{$page['post']['parent_url']}\" title=\"".t('view original post')."\">{$page['post']['parent_poster']}</a>");

			echo " <a href=\"{$page['post']['parent_diffurl']}\" title=\"".t('compare differences')."\">".t('view diff')."</a>)";
			}
		}

		echo "<br/>";

		echo "<a href=\"{$page['post']['spamurl']}\" title=\"".t('report abuse')."\">".t('report abuse')."</a> | ";

		if ($page['can_erase'])
		{
			echo "<a href=\"{$page['post']['deleteurl']}\" title=\"".t('delete post')."\">".t('delete post')."</a> | ";
		}



		$followups=0;
		if(isset($page['post']['followups'])) $followups=count($page['post']['followups']);
		if ($followups)
		{
			echo t('View followups from ');
			$sep="";
			foreach($page['post']['followups'] as $idx=>$followup)
			{
				echo $sep."<a title=\"posted {$followup['postfmt']}\" href=\"{$followup['followup_url']}\">{$followup['poster']}</a>";
				$sep=($idx<($followups-2))?", ":(' '.t('and').' ');
			}

			echo " | ";
		}

		/*
		if ($page['post']['parent_pid']>0)
		{
			echo "<a href=\"{$page['post']['parent_diffurl']}\" title=\"".t('compare differences')."\">".t('diff')."</a> | ";
		}
		*/

		echo "<a href=\"{$page['post']['downloadurl']}\" title=\"".t('download file')."\">".t('download')."</a>";

		//echo "<span id=\"copytoclipboard\"></span>";

		//echo "<a href=\"/\" title=\"".t('make new post')."\">".t('new post')."</a>";

		echo "</h1>";

#abuse reports

if ($is_admin)
{

   $abuse = $pastebin->getAbusePost($page['post']['pid']);

   if ($abuse)
   {
       echo '<div style="background:#ffffaa;padding:5px;">';
       echo "<pre>$abuse</pre>";
       echo '</div>';
   }

}

		if (isset($_GET['report']))
		{
		echo '<div id="spamform">';
		echo '<form method="post" action="'.$pastebin->getPostUrl($page['post']['pid']).'">';
		echo '<input  type="hidden" id="spam_pid" name="pid" value="'.$page['post']['pid'].'">';
		echo '<input  type="hidden" id="processabuse" name="processabuse" value="0">';

		echo '<p>'.t('Please indicate why this post is abusive, and provide any other useful information.').'</p>';

		echo '<input type="radio" name="abuse" value="spam" id="abuse_spam">';
		echo '<label for="abuse_spam">'.t('Spam / advertising / junk').'</label><br>';

		echo '<input type="radio" name="abuse" value="personal" id="abuse_personal">';
		echo '<label for="abuse_personal">'.t('Personal details').'</label><br>';

		echo '<input type="radio" name="abuse" value="proprietary" id="abuse_proprietary">';
		echo '<label for="abuse_proprietary">'.t('Proprietary code').'</label><br>';

		echo '<input checked="checked" type="radio" name="abuse" value="other" id="abuse_other">';
		echo '<label for="abuse_other">'.t('Other').'</label><br><br>';

		echo '<label for="comments">'.t('comments (optional)').'</label><br>';
		echo '<textarea style="width:350px" id="comments" name="comments" rows="2" cols="30"></textarea><br><br>';

		echo '<label for="sender">'.t('email (optional)').'</label><br>';
		echo '<input  style="width:350px" type="text" id="sender" name="sender"><br><br>';


		echo '<input type="submit" name="reportspam" value="'.t('send abuse report').'">';
		echo '</form>';
		echo '</div>';
		}



}
if (isset($page['post']['pid']))
{
	echo "<div class=\"syntax\">".$page['post']['codefmt']."</div>";
	echo "<br /><b>".t('Submit a correction or amendment below')." (<a href=\"{$CONF['this_script']}\">".t('click here to make a fresh posting')."</a>)</b><br/>";
	echo t('After submitting an amendment, you\'ll be able to view the differences between the old and new posts easily').'.';
}



if (isset($_GET['help']))
{
	h1('What is pastebin?');
	p('Pastebin is here to help you collaborate on debugging code snippets. '.
		'If you\'re not familiar with the idea, most people use it like this:');

	echo '<ul>';

	li('<a href="/">Submit</a> a code fragment to pastebin, getting a url like ' . $pastebin->getPostUrl("m2a3b4c5d") . '');
	li('Paste the url into an IRC or IM conversation');
	li('Someone responds by reading and perhaps submitting a modification of your code');
	li('You then view the modification, maybe using the built in diff tool to help locate the changes');
	printf(t('<li>To highlight particular lines, prefix each line with %s</li>'),$CONF['highlight_prefix']);


	echo '</ul>';


	h1('How can I view the differences between two posts?');

	p('When you view a post, you have the opportunity of editing the text - '.
		'<strong>this creates a new post</strong>, but when you view it, you\'ll be given a '.
		'\'diff\' link which allows you to compare the changes between the old and the new version');
	p('This is a powerful feature, great for seeing exactly what lines someone changed');


	h1('How can I delete a post?');
	p('If you clicked the "remember me" checkbox when posting, you will be able to delete '.
	'post from the same computer you posted from - simply view the post and click the "delete post" link.');

	h1('What\'s a private pastebin and how do I get one?');

	p('You get a private pastebin simply by thinking up a domain name no-one else is using, '.
	'e.g. ' . $CONF['SCHEME'] . '://private.' . $CONF['basedomain'] . ' .');

	p('Posts made into a subdomain only show up on that domain, making it easy for you to collaborate without the '.
	'\'noise\' of the regular service at <a href="' . $CONF['SCHEME'] . '://' . $CONF['basedomain'] . '">' . $CONF['SCHEME'] . '://' . $CONF['basedomain'] . '</a>');

	p('All you need to do is change the web address in your browser to access a private pastebin, '.
		'or you can simply enter the domain you\'d like below.')
	?>

	<form method="get" action="<?php echo $CONF['this_script']?>">
	<input type="hidden" name="help" value="1"/>
	<p><?php echo t('Go to')?> <?php echo $CONF['SCHEME']?>://<input type="text" name="goprivate" value="<?php if (isset($_GET['goprivate'])) { echo htmlentities(stripslashes($_GET['goprivate'])); } ?>" size="10"/>.<?php echo $CONF['basedomain']?>
	<input type="submit" name="go" value="<?php echo t('Go')?>"/></p>
	<?php if (isset($_GET['goprivate'])) { p('Please use only characters a-z,0-9, dash \'-\', underscore \'_\' and period \'.\'. Your name must start and end with a letter or number.'); } ?>
	</form>
	<?php

	p('Please note that there is no password protection - subdomains are accessible to anyone '.
	'who knows the domain name you\'ve chosen, but we do not publish a list of domains used.');

	h1('Subdomains for your language...');

	p('If a subdomain matches a language name, the required syntax highlighting is selected '.
	'for you, so ruby.' . $CONF['basedomain'] . ' will preselect Ruby automatically. ');

	echo '<p>';

	$sep="";
	foreach($CONF['all_syntax'] as $langcode=>$langname)
	{
		if ($langcode=='text')
			$langname="Plain Text";
		echo "{$sep}<a title=\"{$langname} Pastebin\" href=\"{$CONF['SCHEME']}://{$langcode}.{$CONF['basedomain']}\">{$langname}</a>";
		$sep=", ";
	}

	echo '</p>';

	//h1('How can I secure a post?');
	//p('You can use gpg -ac to password encrypt a post, and gpg -d to decrypt the post.');

        h1('Acceptable Use Policy');
        p('Broadly speaking, the site was created to help programmers. Any post or usage pattern not related to that goal which results in unusually high traffic '.
          'will be flagged for investigation. Your post may be deleted.');
        p('In particular, please do not post email lists, password lists or personal information. The "report abuse" feature can be used to flag such posts and they will be deleted.');
        p('Do not aggressively spider the site.');

        h1('Can I host my own copy of the pastebin software?');
        p('The source code to this site is available under a GPL licence.');

}
else
{
?>
<form name="editor" method="post" action="<?php echo $CONF['this_script']?>">
<input type="hidden" name="parent_pid" value="<?php echo isset($page['post']['pid'])?$page['post']['pid']:'' ?>"/>

<br/>
<?php

echo t('Syntax highlighting:').'<select name="format">';

//show the popular ones
foreach ($CONF['all_syntax'] as $code=>$name)
{
	if (in_array($code, $CONF['popular_syntax']))
	{
		$sel=($code==$page['current_format'])?"selected=\"selected\"":"";
		echo "<option $sel value=\"$code\">$name</option>";
	}
}

echo "<option value=\"text\">----------------------------</option>";

//show all formats
foreach ($CONF['all_syntax'] as $code=>$name)
{
	$sel=($code==$page['current_format'])?"selected=\"selected\"":"";
	if (in_array($code, $CONF['popular_syntax']))
		$sel="";
	echo "<option $sel value=\"$code\">$name</option>";

}
?>
</select><br/>
<br/>

<?php printf(t('To highlight particular lines, prefix each line with %s'),$CONF['highlight_prefix']);

$rows=isset($page['post']['editcode']) ? substr_count($page['post']['editcode'], "\n") : 0;
$rows=min(max($rows,10),40);
?>
<br/>
<textarea id="code" class="codeedit" name="code2" cols="80" rows="<?php echo $rows ?>"><?php
if (!empty($page['post']['editcode'])) {
	echo htmlentities($page['post']['editcode'], ENT_COMPAT,$CONF['htmlentity_encoding']);
}
?></textarea>

<div id="namebox">

<label for="poster"><?php echo t('Your Name')?></label><br/>
<input type="text" maxlength="24" size="24" id="poster" name="poster" value="<?php echo isset($page['poster'])?$page['poster']:'' ?>" />
<input type="submit" name="paste" value="<?php echo t('Send')?>"/>
<br />
<?php echo '<input type="checkbox" name="remember" value="1" '.$page['remember'].' />'.t('Remember me so that I can delete my post'); ?>

</div>


<div id="expirybox">


<div id="expiryradios">
<label><?php echo t('How long should your post be retained?') ?></label><br/>

<input type="radio" id="expiry_hour" name="expiry" value="h" <?php if ($page['expiry']=='h') echo 'checked="checked"'; ?> />
<label id="expiry_hour_label" for="expiry_hour"><?php echo t('an hour') ?></label>

<input type="radio" id="expiry_day" name="expiry" value="d" <?php if ($page['expiry']=='d') echo 'checked="checked"'; ?> />
<label id="expiry_day_label" for="expiry_day"><?php echo t('a day') ?></label>

<input type="radio" id="expiry_month" name="expiry" value="m" <?php if ($page['expiry']=='m') echo 'checked="checked"'; ?> />
<label id="expiry_month_label" for="expiry_month"><?php echo t('a month') ?></label>

<?php if ($is_admin){
echo '<input type="radio" id="expiry_forever" name="expiry" value="f" ';
if ($page['expiry']=='f') echo 'checked="checked"';
echo ' />';
echo '<label id="expiry_forever_label" for="expiry_forever">' . t('forever') . '</label>';
} ?>
</div>

<div id="expiryinfo"></div>

</div>

<div id="email">
<input type="text" size="8" name="email" value="" />
</div>

<div id="end"></div>

</form>
<?php
}
?>

</div>
</body>
</html>
