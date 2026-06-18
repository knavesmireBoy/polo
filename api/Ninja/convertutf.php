<?php
/**
 * https://gist.githubusercontent.com/pobegov/f8b293fb6eb658a13feb1c318e6c07ed/raw/e60e2be180bd49d874d1b52b641459233560346c/convert_latin1_data_in_a_utf8_mysql_database_to_utf8mb4.php
 * Requires php >= 5.5
 * 
 * Use this script to convert utf-8 data in utf-8 mysql tables stored via latin1 connection
 * This is a PHP port from: https://gist.github.com/njvack/6113127
 *
 * BACKUP YOUR DATABASE BEFORE YOU RUN THIS SCRIPT!
 *
 * Once the script ran over your databases, change your database connection charset to utf8:
 *
 * $dsn = 'mysql:host=localhost;port=3306;charset=utf8';
 * 
 * DON'T RUN THIS SCRIPT MORE THAN ONCE!
 *
 * @author hollodotme
 *
 * @author derclops since 2019-07-01
 *
 *         I have taken the liberty to adapt this script to also do the following:
 *
 *         - convert the database to utf8mb4
 *         - convert all tables to utf8mb4
 *         - actually then also convert the data to utf8mb4
 *
 */
//https://mathiasbynens.be/notes/mysql-utf8mb4#character-sets
header('Content-Type: text/plain; charset=utf-8');

$dsn      = 'mysql:host=localhost;port=3306;charset=utf8';
$user     = 'root';
$password = 'root';
$options  = [
	\PDO::ATTR_CURSOR                   => \PDO::CURSOR_FWDONLY,
	\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	\PDO::MYSQL_ATTR_INIT_COMMAND       => "SET CHARACTER SET latin1",
];


$dbManager = new \PDO( $dsn, $user, $password, $options );

$databasesToConvert = [ 'database1',/** database3, ... */ ];
$typesToConvert     = [ 'char', 'varchar', 'tinytext', 'mediumtext', 'text', 'longtext' ];

foreach ( $databasesToConvert as $database )
{
	echo $database, ":\n";
	echo str_repeat( '=', strlen( $database ) + 1 ), "\n";

	$dbManager->exec( "USE `{$database}`" );

	echo "converting database to correct locale too ... \n";

	$dbManager->exec("ALTER DATABASE `{$database}` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");


	$tablesStatement = $dbManager->query( "SHOW TABLES" );
	while ( ($table = $tablesStatement->fetchColumn()) )
	{
		echo "Table: {$table}:\n";
		echo str_repeat( '-', strlen( $table ) + 8 ), "\n";

		$columnsToConvert = [ ];

		$columsStatement = $dbManager->query( "DESCRIBE `{$table}`" );

		while ( ($tableInfo = $columsStatement->fetch( \PDO::FETCH_ASSOC )) )
		{
			$column = $tableInfo['Field'];
			echo ' * ' . $column . ': ' . $tableInfo['Type'];

			$type = preg_replace( "#\(\d+\)#", '', $tableInfo['Type'] );

			if ( in_array( $type, $typesToConvert ) )
			{
				echo " => must be converted\n";

				$columnsToConvert[] = $column;
			}
			else
			{
				echo " => not relevant\n";
			}
		}


		//convert table also!!!
		$convert = "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

		echo "\n", $convert, "\n";
		$dbManager->exec( $convert );
		$databaseErrors = $dbManager->errorInfo();
		if( !empty($databaseErrors[1]) ){
			echo "\n !!!!!!!!!!!!!!!!! ERROR OCCURED ".print_r($databaseErrors, true)." \n";
			exit;
		}


		if ( !empty($columnsToConvert) )
		{
			$converts = array_map(
				function ( $column )
				{
					//return "`{$column}` = IFNULL(CONVERT(CAST(CONVERT(`{$column}` USING latin1) AS binary) USING utf8mb4),`{$column}`)";
					return "`{$column}` = CONVERT(BINARY(CONVERT(`{$column}` USING latin1)) USING utf8mb4)";
				},
				$columnsToConvert
			);

			$query = "UPDATE IGNORE `{$table}` SET " . join( ', ', $converts );

			//alternative
			// UPDATE feedback SET reply = CONVERT(BINARY(CONVERT(reply USING latin1)) USING utf8mb4) WHERE feedback_id = 15015;


			echo "\n", $query, "\n";


			$dbManager->exec( $query );

			$databaseErrors = $dbManager->errorInfo();
			if( !empty($databaseErrors[1]) ){
				echo "\n !!!!!!!!!!!!!!!!! ERROR OCCURED ".print_r($databaseErrors, true)." \n";
				exit;
			}
		}

		echo "\n--\n";
	}

	echo "\n";
}

