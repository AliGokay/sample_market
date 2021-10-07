<?php 

class Database {

    private $db;
    private $db_host;
    private $db_name;
    private $db_user;
    private $db_pass;    
    private $db_charset;    
    public $errors = array();
    private $stmt;
    private $debugIsEnabled=false;
    private $totalrows;

    /**
     * [sınıf örneği oluştururken consructor'da private değişkenler set ediliyor.]
     * @param [type] $host    [sunucu adı]
     * @param [type] $name    [veritabanı adı]
     * @param [type] $user    [veritabanı kullanıcısı adı]
     * @param [type] $pass    [veritabanı kullanıcısı şifre]
     * @param string $charset [default utf8 lazım olursa ezebilirsiniz.]
     */
    public function __construct($host,$name,$user,$pass,$charset="utf8"){

        $this->db_host=$host;
        $this->db_name=$name;
        $this->db_user=$user;
        $this->db_pass=$pass;
        $this->db_charset=$charset;
    }
    /**
     * [bağlantıyı private $db değişkenine atar. ]
     * @param  array  $optionArray 
     */
    function connect($optionArray=array()) {
        $dsnStatement = 'mysql:host=' . $this->db_host . ';dbname=' . $this->db_name.';charset=' . $this->db_charset;

        if( empty($optionArray)){
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                PDO::ATTR_PERSISTENT    => TRUE,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>TRUE,
                PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
                
            );
        }else{
            $options = $optionArray;
        }


        try{
            $this->db = new PDO($dsnStatement, $this->db_user, $this->db_pass, $options);
        }
        catch(PDOException $e){
            $this->errors[] = $e->getMessage();
        }
        return $this->db; // This makes all the magic!!!
    }
    /**
     * [true gönderilirse PDOStatement Object 'i ekrana basar. queryString'i görmek için kullanabilirsiniz.]
     * @param [type] $isEnabled [description]
     */
    public function setDebugParam($isEnabled){
        $this->debugIsEnabled = $isEnabled;
    }

    public function query($sqlQuery){
        $this->stmt = $this->db->prepare($sqlQuery);
    }

    public function queryWithPagination($sqlQuery,$start,$limit){
        $sqlQuery = $sqlQuery. " LIMIT :start, :limit";
        $this->stmt = $this->db->prepare($sqlQuery);

        $this->bind(':start', $start, PDO::PARAM_INT);
        $this->bind(':limit', $limit, PDO::PARAM_INT);

    }

    public function bind($param, $value, $type = null){
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute(){
        if( $this->debugIsEnabled){
            echo "<pre>";
            $this->stmt->debugDumpParams();
            print_r($this->stmt);
            echo "</pre>";
            //$this->debugIsEnabled = false;
        }
        try{
            return $this->stmt->execute();
        }
        catch(PDOException $e){
            $this->errors[] = $e->getMessage();
        }
        
    }

    public function getRows(){
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getField(){
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * [pagination vb. işlemler için. limitli sorgularda, limitsiz toplam row countu döndürür. ]
     * [query de SQL_CALC_FOUND_ROWS göndermek gereklidir.]
     * [örnek query: SQL_CALC_FOUND_ROWS * FROM tabloadi ]
     * @return [assoc array] [result rows]
     */
    public function getRowsWithFoundRows(){
        $this->execute();
        $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->query("SELECT FOUND_ROWS() as totalrows");
        $counter = $this->getRows();
        $this->totalrows = $counter[0]['totalrows'];
        return $rows;

    }
    public function foundRows(){
        return $this->totalrows;
    }

    public function getRowsObject(){
        $this->execute();
        return $this->stmt->fetchObject();
    }

    public function getSingleRow(){
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount(){
        return $this->stmt->rowCount();
    }

    public function lastInsertId(){
        return $this->db->lastInsertId();
    }

    public function beginTransaction(){
        return $this->db->beginTransaction();
    }

    public function endTransaction(){
        return $this->db->commit();
    }

    public function cancelTransaction(){
        return $this->db->rollBack();
    }

    public function debugDumpParams(){
        return $this->stmt->debugDumpParams();
    }

    public function fetchColumn(){
        return $this->stmt->fetchColumn();
    }


}
?>