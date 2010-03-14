<?


/**
 * CsvFile
 *
 * Import from and export to CVS
 *
 * @author    $Author: ramses $
 * @access    public
 */
class CsvFile 
{
  private $delimiter = ",";
	private $fields;
  
	public function __construct() 
	{
		$this->fields = array();
	}

	public function getFields()
	{
		return $this->fields;
	}
	
	/**
   * Set csv delimiter
   * 
   * @param  string   $s_delimiter				          new delimiter
   * @access public
   */
	public function setDelimiter($value) 
	{
		$this->delimiter = $value;
	}
	
	/**
   * Set csv delimiter
   * 
   * @param  string   $s_delimiter				          new delimiter
   * @access public
   */
	function getDelimiter() 
	{
		return $this->delimiter;
	}
	
	
	/**
   * Import data from a csv file into the array
   * 
   * @param  string   $s_filename				      filename of the csv file
   * @param  bool     $b_first_row_fieldname	First row contains fieldnames.
   * @access public
   */
	public function import($filename, $firstRowFieldname=true, $maxLength = 2000)
	{
		$this->fields = array();
		$retval = array();
		
		if (!file_exists($filename)) throw new Exception("file $filename does not exist.");
		if (!is_readable($filename)) throw new Exception("file $filename is not readable.");
		
		if(!$fp = fopen($filename,"r")) throw new Exception("Error opening file $filename for reading.");
		
		// head field names
		if(!feof($fp) && $firstRowFieldname) $this->fields = fgetcsv($fp, $maxLength, $this->delimiter);

		while(!feof($fp))
		{
			$line = fgetcsv($fp, $maxLength, $this->delimiter);
			if(!$line) continue;

			// if fieldnames are defined, use field name as key of array
			if($this->fields)
			{
				$element = array();
				foreach($line as $key=>$item)
				{
					$element[$this->fields[$key]] = trim($item);
				}
			}
			else
				$element = $line;

			$retval[] = $element;

		}
		fclose($fp);
		return $retval;
	}
	
	
	
  /**
   * Formats a single cvs line
   * 
   * @param  array     $a_values				     Reference to the value array
   * @return string    Formated CSV line 
   * @access private
   */
	private function formatLine($values)
	{	
		$retval = array();
		
		foreach($values as $item) 
		{
			$retval[] = is_array($item) ? $this->formatLine($item) : sprintf('"%s"', Utils::nl2var(str_replace('"', '""', $item),' '));
		}

		return join($this->delimiter, $retval);
	}
	
	/**
   * Export to a file or return csv file
   * 
   * @param  array    $a_values				       array with all the values
   * @param  string   $s_filename				     optional filename to export to
   * @param  bool     $b_addheader				   make sure first row contains the field names
   * @return array list of csv lines
   * @access public
   */
	function array2csv($values, $addheader=true)
	{
		if (!$values || !is_array($values)) throw new Exception("No values specified for export");

    $retval = array();
    
		reset($values);

		if($addheader)
		{
			$this->fields = current($values);
		  $retval[] = $this->formatLine(array_keys($this->fields));
		}

		foreach($values as $item) 
		{
		  $retval[] = $this->formatLine($item);
		}
		
		return $retval;
	}

	public function save($values, $filename)
	{
		if(!$values) throw new Exception("csv values not set.");
		if(!$filename) throw new Exception("filename not set.");

		// try to open csv file 
		if(!$fp = fopen($filename,"w")) throw new Exception("Error opening file $filename.");

		foreach($values as $item)
		{
			fputs($fp, $item);
		}

		fclose($fp);
		chmod($filename, 0644);
	}
}
?>
