<?php
/**
 * $Project: Pastebin $
 * $Id: default.conf.php,v 1.3 2006/04/27 16:19:24 paul Exp $
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
* This is the main configuration file containing typical defaults.
*
* For ease of upgrading, DO NOT MODIFY THIS FILE!
*
* Create an override file with a name matching your domain or element of
* of it. For example for the domain 'banjo.pastebin.com', the code will
* attempt to include these config files in order
*
* default.conf.php
* com.conf.php
* pastebin.com.conf.php
* banjo.pastebin.com.conf.php
*
* The purpose of this to allow you to specific global options lower down,
* say in com.conf.php, but domain-specific overrides in higher up files like
* banjo.pastebin.com.conf.php
*/



/**
* Site title
*/
$CONF['title']='pastebin';

/**
* Email address feedback should be sent to
*/
$CONF['feedback_to']=null;

/**
* Apparent sender address for feedback email
*/
$CONF['feedback_sender']=null;

/**
* database type - can be file or mysql
*/
$CONF['dbsystem']='file';

/**
* db credentials
*/
$CONF['dbhost']='localhost';
$CONF['dbname']='pastebin';
$CONF['dbuser']='pastebin';
$CONF['dbpass']='banjo';

/**
* administrative password - lets you log on to delete posts
*/
$CONF['admin']='banjo';

/**
 * format of urls to pastebin entries - %d is the placeholder for
 * the entry id.
 *
 * 1. for shortest possible url generation in conjuction with mod_rewrite:
 *    $CONF['url_format']='/%s';
 *
 * 2. if using pastebin with mod_rewrite, but within a subdir, you'd use
 *    something like this:
 *    $CONF['url_format']="/mysubdir/%s";
 *
 * 3. if not using mod_rewrite, you'll need something more like this:
 *    $CONF['url_format']="/pastebin.php?show=%s";
 */
$CONF['url_format']='/?show=%s';



/**
* default expiry time d (day) m (month) or f (forever)
*/
$CONF['default_expiry']='m';

/**
* Allow forever posts
*/
$CONF['allow_forever']=false;

/**
* this is the path to the script - you may want to
* to use / for even shorter urls if the main script
* is renamed to index.php
*/
$CONF['this_script']='/';

/**
* what's the maximum number of posts we want to keep?
* Set this to 0 to have no limit on retained posts
*/
$CONF['max_posts']=0;

/**
* what's the highlight char?
*/
$CONF['highlight_prefix']='@@';

/**
* how many elements in the base domain name? This is used to determine
* what makes a "private" pastebin, i.e. for pastebin.com there are 2
* elements 'pastebin' and 'com' - for pastebin.mysite.com there 3. Got it?
* Good!
*/
$CONF['base_domain_elements']=2;


/**
* maintainer mode enables some code used to aid translation - unless you
* are involved in developing pastebin, leave this as false
*/
$CONF['maintainer_mode']=false;

/**
* default syntax highlighter
*/
$CONF['default_highlighter']='text';

/**
* available formats
*/
$CONF['all_syntax']=array(
	'abap' => 'ABAP',
	'actionscript' => 'ActionScript',
	'actionscript3' => 'ActionScript 3',
	'ada' => 'Ada',
	'aimms' => 'AIMMS3',
	'algol68' => 'ALGOL 68',
	'apache' => 'Apache configuration',
	'applescript' => 'AppleScript',
	'apt_sources' => 'Apt sources',
	'arm' => 'ARM ASSEMBLER',
	'asm' => 'ASM',
	'asp' => 'ASP',
	'asymptote' => 'asymptote',
	'autoconf' => 'Autoconf',
	'autohotkey' => 'Autohotkey',
	'autoit' => 'AutoIt',
	'avisynth' => 'AviSynth',
	'awk' => 'awk',
	'bascomavr' => 'BASCOM AVR',
	'bash' => 'Bash',
	'basic4gl' => 'Basic4GL',
	'biblatex' => 'BibLaTeX',
	'bibtex' => 'BibTeX',
	'blitzbasic' => 'BlitzBasic',
	'bnf' => 'bnf',
	'boo' => 'Boo',
	'bf' => 'Brainfuck',
	'c' => 'C',
	'c_loadrunner' => 'C (LoadRunner)',
	'c_mac' => 'C (Mac)',
	'c_winapi' => 'C (WinAPI)',
	'csharp' => 'C#',
	'cpp' => 'C++',
	'cpp-qt' => 'C++ (Qt)',
	'cpp-winapi' => 'C++ (WinAPI)',
	'caddcl' => 'CAD DCL',
	'cadlisp' => 'CAD Lisp',
	'ceylon' => 'Ceylon',
	'cfdg' => 'CFDG',
	'chaiscript' => 'ChaiScript',
	'chapel' => 'Chapel',
	'cil' => 'CIL',
	'clojure' => 'Clojure',
	'cmake' => 'CMake',
	'cobol' => 'COBOL',
	'coffeescript' => 'CoffeeScript',
	'cfm' => 'ColdFusion',
	'css' => 'CSS',
	'cuesheet' => 'Cuesheet',
	'd' => 'D',
	'dart' => 'Dart',
	'dcl' => 'DCL',
	'dcpu16' => 'DCPU-16 Assembly',
	'dcs' => 'DCS',
	'delphi' => 'Delphi',
	'diff' => 'Diff',
	'div' => 'DIV',
	'dos' => 'DOS',
	'dot' => 'dot',
	'e' => 'E',
	'ecmascript' => 'ECMAScript',
	'eiffel' => 'Eiffel',
	'email' => 'eMail (mbox)',
	'epc' => 'EPC',
	'erlang' => 'Erlang',
	'euphoria' => 'Euphoria',
	'ezt' => 'EZT',
	'fsharp' => 'F#',
	'falcon' => 'Falcon',
	'fo' => 'FO (abas-ERP)',
	'f1' => 'Formula One',
	'fortran' => 'Fortran',
	'freebasic' => 'FreeBasic',
	'freeswitch' => 'FreeSWITCH',
	'4cs' => 'GADV 4CS',
	'gambas' => 'GAMBAS',
	'gdb' => 'GDB',
	'genero' => 'genero',
	'genie' => 'Genie',
	'glsl' => 'glSlang',
	'gml' => 'GML',
	'gettext' => 'GNU Gettext',
	'make' => 'GNU make',
	'octave' => 'GNU/Octave',
	'gnuplot' => 'Gnuplot',
	'go' => 'Go',
	'groovy' => 'Groovy',
	'gwbasic' => 'GwBasic',
	'haskell' => 'Haskell',
	'haxe' => 'Haxe',
	'hicest' => 'HicEst',
	'hq9plus' => 'HQ9+',
	'html4strict' => 'HTML',
	'html5' => 'HTML5',
	'icon' => 'Icon',
	'ini' => 'INI',
	'inno' => 'Inno',
	'intercal' => 'INTERCAL',
	'io' => 'Io',
	'ispfpanel' => 'ISPF Panel',
	'j' => 'J',
	'java' => 'Java',
	'java5' => 'Java(TM) 2 Platform Standard Edition 5.0',
	'javascript' => 'Javascript',
	'jcl' => 'JCL',
	'jquery' => 'jQuery',
	'julia' => 'Julia',
	'kixtart' => 'KiXtart',
	'klonec' => 'KLone C',
	'klonecpp' => 'KLone C++',
	'kotlin' => 'Kotlin',
	'latex' => 'LaTeX',
	'ldif' => 'LDIF',
	'lb' => 'Liberty BASIC',
	'lisp' => 'Lisp',
	'llvm' => 'LLVM Intermediate Representation',
	'locobasic' => 'Locomotive Basic',
	'logtalk' => 'Logtalk',
	'lolcode' => 'LOLcode',
	'lotusformulas' => 'Lotus Notes @Formulas',
	'lotusscript' => 'LotusScript',
	'lscript' => 'LScript',
	'lsl2' => 'LSL2',
	'lua' => 'Lua',
	'magiksf' => 'MagikSF',
	'mapbasic' => 'MapBasic',
	'mathematica' => 'Mathematica',
	'matlab' => 'Matlab M',
	'mercury' => 'Mercury',
	'metapost' => 'MetaPost',
	'mpasm' => 'Microchip Assembler',
	'reg' => 'Microsoft Registry',
	'mirc' => 'mIRC Scripting',
	'mk-61' => 'MK-61/52',
	'mmix' => 'MMIX',
	'modula2' => 'Modula-2',
	'modula3' => 'Modula-3',
	'6502acme' => 'MOS 6502 (6510) ACME Cross Assembler format',
	'6502kickass' => 'MOS 6502 (6510) Kick Assembler format',
	'6502tasm' => 'MOS 6502 (6510) TASM/64TASS 1.46 Assembler format',
	'68000devpac' => 'Motorola 68000 - HiSoft Devpac ST 2 Assembler format',
	'm68k' => 'Motorola 68000 Assembler',
	'mxml' => 'MXML',
	'mysql' => 'MySQL',
	'nagios' => 'Nagios',
	'netrexx' => 'NetRexx',
	'newlisp' => 'newlisp',
	'nginx' => 'nginx',
	'nimrod' => 'Nimrod',
	'nsis' => 'NSIS',
	'oberon2' => 'Oberon-2',
	'objeck' => 'Objeck Programming Language',
	'objc' => 'Objective-C',
	'ocaml' => 'OCaml',
	'ocaml-brief' => 'OCaml (brief)',
	'oorexx' => 'ooRexx',
	'pf' => 'OpenBSD Packet Filter',
	'oobas' => 'OpenOffice.org Basic',
	'oracle11' => 'Oracle 11 SQL',
	'oracle8' => 'Oracle 8 SQL',
	'oxygene' => 'Oxygene',
	'oz' => 'OZ',
	'parasail' => 'ParaSail',
	'parigp' => 'PARI/GP',
	'pascal' => 'Pascal',
	'pcre' => 'PCRE',
	'per' => 'per',
	'perl' => 'Perl',
	'perl6' => 'Perl 6',
	'phix' => 'Phix',
	'php' => 'PHP',
	'php-brief' => 'PHP (brief)',
	'pic16' => 'PIC16',
	'pike' => 'Pike',
	'pixelbender' => 'Pixel Bender 1.0',
	'pli' => 'PL/I',
	'plsql' => 'PL/SQL',
	'postgresql' => 'PostgreSQL',
	'postscript' => 'PostScript',
	'povray' => 'POVRAY',
	'powerbuilder' => 'PowerBuilder',
	'powershell' => 'PowerShell',
	'proftpd' => 'ProFTPd configuration',
	'progress' => 'Progress',
	'prolog' => 'Prolog',
	'properties' => 'PROPERTIES',
	'providex' => 'ProvideX',
	'purebasic' => 'PureBasic',
	'python' => 'Python',
	'pycon' => 'Python (console mode)',
	'pys60' => 'Python for S60',
	'q' => 'q/kdb+',
	'qbasic' => 'QBasic/QuickBASIC',
	'qml' => 'QML',
	'rsplus' => 'R / S+',
	'racket' => 'Racket',
	'rails' => 'Rails',
	'rbs' => 'RBScript',
	'rebol' => 'REBOL',
	'rexx' => 'rexx',
	'robots' => 'robots.txt',
	'rpmspec' => 'RPM Specification File',
	'ruby' => 'Ruby',
	'rust' => 'Rust',
	'sas' => 'SAS',
	'sass' => 'Sass',
	'scala' => 'Scala',
	'scheme' => 'Scheme',
	'scilab' => 'SciLab',
	'scl' => 'SCL',
	'sdlbasic' => 'sdlBasic',
	'smalltalk' => 'Smalltalk',
	'smarty' => 'Smarty',
	'spark' => 'SPARK',
	'sparql' => 'SPARQL',
	'sql' => 'SQL',
	'standardml' => 'StandardML',
	'stonescript' => 'StoneScript',
	'swift' => 'Swift',
	'systemverilog' => 'SystemVerilog',
	'tsql' => 'T-SQL',
	'tcl' => 'TCL',
	'tclegg' => 'TCLEGG',
	'teraterm' => 'Tera Term Macro',
	'texgraph' => 'TeXgraph',
	'text' => 'Text',
	'thinbasic' => 'thinBasic',
	'twig' => 'Twig',
	'typoscript' => 'TypoScript',
	'unicon' => 'Unicon (Unified Extended Dialect of Icon)',
	'idl' => 'Uno Idl',
	'uscript' => 'Unreal Script',
	'upc' => 'UPC',
	'urbi' => 'Urbi',
	'vala' => 'Vala',
	'vbnet' => 'vb.net',
	'vbscript' => 'VBScript',
	'vedit' => 'Vedit macro language',
	'verilog' => 'Verilog',
	'vhdl' => 'VHDL',
	'vim' => 'Vim Script',
	'vb' => 'Visual Basic',
	'visualfoxpro' => 'Visual Fox Pro',
	'visualprolog' => 'Visual Prolog',
	'whitespace' => 'Whitespace',
	'whois' => 'Whois (RPSL format)',
	'winbatch' => 'Winbatch',
	'batch' => 'Windows Batch file',
	'xpp' => 'X++',
	'xbasic' => 'XBasic',
	'xml' => 'XML',
	'xojo' => 'Xojo',
	'xorg_conf' => 'Xorg configuration',
	'yaml' => 'YAML',
	'z80' => 'ZiLOG Z80 Assembler',
	'zxbasic' => 'ZXBasic',
);

/**
* popular formats, listed first
*/
$CONF['popular_syntax']=array(
	'bash',
	'c',
	'cpp',
	'css',
	'diff',
	'erlang',
	'go',
	'html5',
	'java',
	'javascript',
	'latex',
	'lua',
	'perl',
	'php',
	'python',
	'ruby',
	'rust',
	'sql',
	'text',
	'xml',

);

?>
