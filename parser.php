<?php
$Ver2Use = 'P23';
$File2Import = 'ESOUIDocumentation' . $Ver2Use . '.txt';
$SubDirOut = $Ver2Use . '\\';

$topLevels = array('Global Variables' => array('MaxLinesPerEntry' => 1),
				 'Events' => array('MaxLinesPerEntry' => 1),
				 'UI XML Layout' => array('MaxLinesPerEntry' => 1),
				 'Game API' => array('MaxLinesPerEntry' => 2),
				 'Object API' => array('MaxLinesPerEntry' => 2));

/*
 wiki pages to update
 https://wiki.esoui.com/Globals
 https://wiki.esoui.com/Events
 	- have to be done manually since the wiki is grouped and the original doc file isn't
	- compare old txt to new txt, make changes in wiki as needed
 https://wiki.esoui.com/UI_XML
 https://wiki.esoui.com/API
 https://wiki.esoui.com/Controls
 */

$CurrLevel = '';
$CurrSubLevel = '';

$APIVer = '';

$handle = fopen($File2Import, 'r');
if ( $handle )
	{
	while ( ($buffer = fgets($handle)) != false )
		{
		$buffer = rtrim($buffer);
		if ( strlen($buffer) == 0 )
			{
			continue;
			}

		if ( !(strpos($buffer, 'ESO UI Documentation for API Version') === false) )
			{
			$APIVer = substr($buffer, -6);
			continue;
			}

		$ay_out = array();

		if ( preg_match('/^h[0-9]. /', $buffer, $ay_out) == 1)
			{
			if ( substr($buffer, 0, 2) == 'h2' )
				{
				$CurrLevel = substr($buffer, 4);
				$CurrSubLevel = 'Default';
				}
			else
				{
				$CurrSubLevel = substr($buffer, 4);
				if ( !isset($topLevels[$CurrLevel][$CurrSubLevel]) )
					{
					$topLevels[$CurrLevel][$CurrSubLevel] = array();
					}
				}
			continue;
			}

		if ( !isset($topLevels[$CurrLevel]['MaxLinesPerEntry']) )
			{
			continue;
			}

		// don't remove this, there are instances where it doesn't yet exist and needs creating, but not always
		if ( !isset($topLevels[$CurrLevel][$CurrSubLevel]) )
			{
			$topLevels[$CurrLevel][$CurrSubLevel] = array();
			}


		if ( $topLevels[$CurrLevel]['MaxLinesPerEntry'] > 1 )
			{
			$newEntry = array($buffer);
			$newLine = rtrim(fgets($handle));
			if ( $newLine > '' )
				{
				array_push($newEntry, $newLine);

				$newLine = rtrim(fgets($handle));   		// some have a 3rd line
				if ( $newLine > '' )
					{
					array_push($newEntry, $newLine);
					}
				}

			array_push($topLevels[$CurrLevel][$CurrSubLevel], $newEntry);
			}
		else
			{
			array_push($topLevels[$CurrLevel][$CurrSubLevel], $buffer);
			}
		}
	}

function GameAPISort($a, $b)
	{
	return ($a[0] >= $b[0]);
	}  //  GameAPISort

function WriteAPIFiltered($filter)
	{
	global $topLevels;
	global $SubDirOut;

	$handle = fopen($SubDirOut . 'GameAPI_' . $filter . '_Funcs.txt', 'w');
	foreach($topLevels['Game API']['Default'] as $index => $entry)
		{
		if ( strpos($entry[0], $filter . ' function') > 0 )
			{
			fwrite($handle, $entry[0] . "\r\n");
			}
		}
	fwrite($handle, "\r\n");
	fclose($handle);
	}  //  WriteAPIFiltered

function ProcessGameAPI()
	{
	global $topLevels;
	global $SubDirOut;

	usort($topLevels['Game API']['Default'], "GameAPISort");

	$handle1 = fopen($SubDirOut . 'GameAPI.txt', 'w');
	foreach ( $topLevels['Game API']['Default'] as $index => $entry )
		{
		$searchfor = array(
			'** _Returns:_',
			'*string*',
			'*bool*',
			'*integer*',
			'*number*',
			'*luaindex*',
			'*id64*',
			'*textureName*',
			'*luaindex:nilable*',
			'*string:nilable*',
			'*integer:nilable*',
			'*bool:nilable*',
			'*number:nilable*',
			'*id64:nilable*',
			'*textureName:nilable*',
			'_Uses variable returns..._' );

		$replacewith = array(
			"** '''Returns:'''",
			"'''string'''",
			"'''boolean'''",
			"'''number'''",
			"'''number'''",
			"'''number'''",
			"'''id64'''",
			"'''textureName'''",
			"'''number:nilable'''",
			"'''string:nilable'''",
			"'''number:nilable'''",
			"'''boolean:nilable'''",
			"'''number:nilable'''",
			"'''id64:nilable'''",
			"'''textureName:nilable'''",
			"''Uses variable returns...''" ) ;

		foreach($entry as $subindex => $subentry)
			{
			$entry[$subindex] = str_replace($searchfor, $replacewith, $subentry);
			}

		$searchfor = array(
			'/\*\[(\w+)\|\#(\w+)\]\*' . '/',
			'/\*\[(\w+)\|\#(\w+)\]\:nilable\*' . '/',
			'/(.+) \*private\* (.+)/',
			'/(.+) \*protected\* (.+)/',
			'/\* (\{\{.*\}\}) \* (\w+)/',
			'/\* (\w+)\(/',
			'/ _(\w+)_/' );

		$replacewith = array(
			"'''number''' [[Globals#\\1|\\1]]",
			"'''number:nilable''' [[Globals#\\1|\\1]]",
			'* {{Private function}} \1\2',
			'* {{Protected function}} \1\2',
			'* {{GitHubSearch|Search=\2}} \1 [[\2]]',
			'* {{GitHubSearch|Search=\1}} [[\1]](',
			" ''\\1''" );

		foreach($entry as $subindex => $subentry)
			{
			$entry[$subindex] = preg_replace($searchfor, $replacewith, $subentry);
			}

		$topLevels['Game API']['Default'][$index] = $entry;

		foreach($entry as $subindex => $subentry)
			{
			fwrite($handle1, $subentry . "\r\n");
			}
		fwrite($handle1, "\r\n");

		}
	fclose($handle1);

	WriteAPIFiltered('Protected');
	WriteAPIFiltered('Private');
	}  //  ProcessGameAPI

// ProcessGameAPI()


function ProcessEvents()
	{
	global $topLevels;
	global $SubDirOut;

	sort($topLevels['Events']['Default']);

	$handle = fopen($SubDirOut . 'Events.txt', 'w');
	foreach ( $topLevels['Events']['Default'] as $index => $entry )
		{
		$searchfor = array(
			'*string*',
			'*bool*',
			'*integer*',
			'*number*',
			'*luaindex*',
			'*id64*',
			'*textureName*',
			'*luaindex:nilable*',
			'*string:nilable*',
			'*integer:nilable*',
			'*bool:nilable*',
			'*number:nilable*',
			'*id64:nilable*',
			'*textureName:nilable*' );
	//		'_' );

		$replacewith = array(
			"'''string'''",
			"'''boolean'''",
			"'''number'''",
			"'''number'''",
			"'''number'''",
			"'''id64'''",
			"'''textureName'''",
			"'''number:nilable'''",
			"'''string:nilable'''",
			"'''number:nilable'''",
			"'''boolean:nilable'''",
			"'''number:nilable'''",
			"'''id64:nilable'''",
			"'''textureName:nilable'''" ) ;
	//		"''" );
		$entry = str_replace($searchfor, $replacewith, $entry);

		$searchfor = array(
			'/^\* (\w+) \(/',
			'/^\* (\w+)/',
			'/\*\[(\w+)\|\#(\w+)\]\*' . '/',
			'/\*\[(\w+)\|\#(\w+)\]\:nilable\*' . '/',
			'/ _(\w+)_/' );

		$replacewith = array(
			"* {{GitHubSearch|Search=\\1}} [[\\1]] ('''number''' [[Constant_Values#\\1|''eventCode'']], ",
			"* {{GitHubSearch|Search=\\1}} [[\\1]] ('''number''' [[Constant_Values#\\1|''eventCode'']])",
			"'''number''' [[Globals#\\1|\\1]]",
			"'''number:nilable''' [[Globals#\\1|\\1]]",
			" ''\\1''" );

		$entry = preg_replace($searchfor, $replacewith, $entry);

//		$topLevels['Events']['Default'][$index] = $entry;

		fwrite($handle, $entry . "\r\n");

		}
	fclose($handle);

	}  //  ProcessEvents

function ProcessObjectAPI()
	{
	global $topLevels;
	global $SubDirOut;

	$handle = fopen($SubDirOut . 'ObjectAPI.txt', 'w');
	foreach ( $topLevels['Object API'] as $index => $entry )
		{
		if ( gettype($entry) != 'array' )
			{
			continue;
			}
		fwrite($handle, '===' . $index . "===\r\n");
		foreach ( $entry as $subindex => $subentry  )
			{
			foreach ( $subentry as $subsubindex => $subsubentry )
				{
				$searchfor = array(
					'** _Returns:_',
					'*string*',
					'*bool*',
					'*integer*',
					'*number*',
					'*luaindex*',
					'*id64*',
					'*textureName*',
					'*luaindex:nilable*',
					'*string:nilable*',
					'*integer:nilable*',
					'*bool:nilable*',
					'*number:nilable*',
					'*id64:nilable*',
					'*textureName:nilable*'
					);

				$replacewith = array(
					"** '''Returns:'''",
					"'''string'''",
					"'''boolean'''",
					"'''number'''",
					"'''number'''",
					"'''number'''",
					"'''id64'''",
					"'''textureName'''",
					"'''number:nilable'''",
					"'''string:nilable'''",
					"'''number:nilable'''",
					"'''boolean:nilable'''",
					"'''number:nilable'''",
					"'''id64:nilable'''",
					"'''textureName:nilable'''",
					);
				$subsubentry = str_replace($searchfor, $replacewith, $subsubentry);

				$searchfor = array(
					'/\*\[(\w+)\|\#(\w+)\]\*/',
					'/^\* (\w+) \(/',
					'/\*\[(\w+)\|\#(\w+)\]\:nilable\*' . '/',
					'/\* (.+) \*protected-attributes\* (.+)/',
					'/\* (.+) \*private\* (.+)/',
					'/ _(\w+)_/',
					'/\]$/',
					'/\[(\w+)\|#(\w+)\], /',
					'/\*(\w+)\*/',
					'/\* (\{\{.*\}\}) (\w+)/',
					'/\* (\w+)\(/'

					 );

				$replacewith = array(
					"'''number''' [[Globals#\\1|\\1]]",
					"* {{GitHubSearch|Search=\\1}} [[\\1]] (",
					"'''number:nilable''' [[Globals#\\1|\\1]]",
					'* {{Protected_attributes}} \1\2',
					'* {{Private function}} \1\2',
					" ''\\1''",
					"], ",
					"* [[Controls#\\1|\\2]]\r\n",
					"'''\\1'''",
					'* {{GitHubSearch|Search=\2}} \1 [[\2]]',
					'* {{GitHubSearch|Search=\1}} [[\1]]('
					 );

				$subsubentry = preg_replace($searchfor, $replacewith, $subsubentry);

//				$topLevels['Object API'][$index][$subindex][$subsubindex] = $subsubentry;

				fwrite($handle, $subsubentry . "\r\n");
				}
			fwrite($handle, "\r\n");
			}
		fwrite($handle, "\r\n");
		}
	fclose($handle);

	}  //  ProcessObjectAPI

function ProcessGlobalVars()
	{
	global $topLevels;
	global $SubDirOut;

	ksort($topLevels['Global Variables']);

	$handle = fopen($SubDirOut . 'GlobalVariables.txt', 'w');
	foreach ( $topLevels['Global Variables'] as $index => $entry )
		{
		if ( gettype($entry) != 'array' )
			{
			continue;
			}
		fwrite($handle, '===' . $index . "===\r\n");
		foreach ( $entry as $subindex => $subentry  )
			{
			$searchfor = array(
				'/^\* ([\w_]+)/'
				 );

			$replacewith = array(
				'*[[Constant_Values#\1|\1]]'
				 );

			$subentry = preg_replace($searchfor, $replacewith, $subentry);

//			$topLevels['Global Variables'][$index][$subindex] = $subentry;

			fwrite($handle, $subentry . "\r\n");
			}
		fwrite($handle, "\r\n\r\n");
		}
	fclose($handle);

	}  //  ProcessGlobalVars

function ProcessUIXML()
	{
	global $topLevels;
	global $SubDirOut;

	//ksort($topLevels['UI XML Layout']);

	$handle = fopen($SubDirOut . 'UI_XML_Layout.txt', 'w');
	foreach ( $topLevels['UI XML Layout'] as $index => $entry )
		{
		if ( gettype($entry) != 'array' )
			{
			continue;
			}
		fwrite($handle, '===' . str_replace(':', '', $index) . "===\r\n");
		if ( !isset($entry[0]) )
			{
			fwrite($handle, "\r\n");
			}
		foreach ( $entry as $subindex => $subentry  )
			{
			$searchfor = array(
				'/\* (\w+) \*bool\*/',
				'/\* (\w+) \*number\*/',
				'/\* (\w+) \*integer\*/',
				'/\* (\w+) \*string\*/',
				'/\* (\w+) \*\[(\w+)\|#(\w+)\]\*/',
				'/\* \[(\w+)\: (\w+)\|#(\w+)\]/',
				'/\* _(\w+)\:_ \*number\* _(\w+)_/',
				'/\* _(\w+)\:_ \*string\* _(\w+)_/',
				'/\* _(\w+)\:_ \*bool\* _(\w+)_/',
				'/\* _(\w+)\:_ \*integer\* _(\w+)_/',
				'/\* _(\w+)\:_ \*\[(\w+)\|#(\w+)\]\* _(\w+)_/',
				'/\* ScriptArguments: local self(.*)/'
				 );

			$replacewith = array(
				"* '''boolean''' ''\\1''",
				"* '''number''' ''\\1''",
				"* '''number''' ''\\1''",
				"* '''string''' ''\\1''",
				"* '''number''' '''[[Globals#\\2|\\3]]''' ''\\1''",
				"* [[#\\3|\\1: \\2]]",
				"* ''\\1:'' '''number''' ''\\2''",
				"* ''\\1:'' '''string''' ''\\2''",
				"* ''\\1:'' '''boolean''' ''\\2''",
				"* ''\\1:'' '''number''' ''\\2''",
				"* ''\\1:'' '''[[Globals#\\2|\\3]]''' ''\\4''",
				"* '''ScriptArguments:''' ''local self\\1''"
				);

			$subentry = preg_replace($searchfor, $replacewith, $subentry);

//			$topLevels['UI XML Layout'][$index][$subindex] = $subentry;

			fwrite($handle, $subentry . "\r\n");
			}
		fwrite($handle, "\r\n\r\n");
		}
	fclose($handle);

	}  //  ProcessUIXML

ProcessGameAPI();
ProcessEvents();
ProcessObjectAPI();
ProcessGlobalVars();
ProcessUIXML();

//print_r($APIVer);
//print_r($topLevels);
//print_r($topLevels['Game API']['Default']);
//print_r($topLevels['UI XML Layout']);
