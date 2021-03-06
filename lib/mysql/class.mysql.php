<?php
class MySQL implements dbInterface
{
	private $_con				= false;
	private $_res_resource		= null;
	private $_res_num			= 0;
	private $_affected_rows		= 0;
	private $_last_inserted_id	= 0;
	private $_queryExecutionTime= 0;


	public function __construct()
	{
		try {
			$this->_con = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);
			if (!$this->_con) {
				throw new DBException('Problem with the connection to the mysql server. Error: '.mysqli_connect_error());
			}
			if (!mysqli_select_db($this->_con, DB_DATABASE)) {
				throw new DBException('Database '.DB_DATABASE.' not found!!');
			}
			mysqli_set_charset($this->_con, 'utf8'); 
		} catch (DBException $e) {
			$e->handleError();
		}
	}

	/**
	 * Native function for executing mysql query
	 */
	private function executeQuery($q)
	{
		if (empty($q)) {
			throw new DBException('Empty query submitted!');
		}
		$start =microtime(true);
		$this->_res_resource = mysqli_query($this->_con, $q);
		$end = $msc=microtime(true);
		$this->_queryExecutionTime = $end-$start;
		if (!$this->_res_resource) {
			throw new DBException(mysqli_error($this->_con));
		}
		
		if (preg_match('/^SELECT.+/',strtoupper($q))) {
			$this->_res_num = @mysqli_num_rows($this->_res_resource);
		}
		elseif (preg_match('/^INSERT.+/',strtoupper($q))) {
			$this->_last_inserted_id = mysqli_insert_id($this->_con);
			$this->_affected_rows = @mysqli_affected_rows($this->_con);
		}
		else{
			$this->_affected_rows = @mysqli_affected_rows($this->_con);
		}
		
		return true;
	}

	/**
	 * Fetch all results for current mysql result resource and return as an associative array
	 *
	 * @access public
	 * @param none
	 * @return array
	 */
	private function fetchRowsFromResource()
	{
		$results = array();
		while($row = mysqli_fetch_assoc($this->_res_resource)) {
			$results[] = $row;
		}
		return $results;
	}

	/**
	 * Get one row from the database according to submitted query
	 *
	 * @access public
	 * @param string $q mysql query
	 * @return array Array with data from mysql. Can be empty.
	 */
	public function fetch($q)
	{
		$this->executeQuery($q);
		return mysqli_fetch_assoc($this->_res_resource);
	}
	/**
	 * Get all rows from the database which satisfy the
	 * conditions from submitted mysql query
	 *
	 * @access public
	 * @param string $q mysql query
	 * @return array Array with data from mysql. Can be empty.
	 */
	public function fetchAll($q)
	{
		$this->executeQuery($q);
		return $this->fetchRowsFromResource();

	}

	/**
	 * Execute a SQL statement and return the number of affected rows
	 *
	 * @access public
	 * @param string $q mysql statement
	 */
	public function execute($q)
	{
		$this->executeQuery($q);
		return $this->_affected_rows;
	}

	/**
	 * Execute a SQL statement and return true on success or false on failure
	 *
	 * @access public
	 * @param string $q SQL statement
	 * @return bool true on success otherwise false
	 *
	 */
	public function query($q)
	{
		return $this->executeQuery($q);
	}

	/**
	 * Return last inserted id
	 *
	 * @access public
	 * @param none
	 * @return int Id of last inserted row in database
	 */
	public function insertId()
	{
		return $this->_last_inserted_id;
	}

	/**
	 * Return the number of all found rows from the last SQL query
	 * @access public
	 * @param none
	 * @return int Number of rows or 0
	 */
	public function foundedRows()
	{
			
	}

	public function resNum()
	{
		return $this->_res_num;
	}

	public function escape($value)
	{
		$value = strip_tags(trim($value));
		return mysqli_real_escape_string($this->_con, $value);
	}

	public function mysql_real_escape($value)
	{
		return mysqli_real_escape_string($this->_con, $value);
	}

	public function getQueryExecutionTime()
	{
		return $this->_queryExecutionTime;
	}
}