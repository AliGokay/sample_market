<?php

class Logs {
    private $database;
    private $logType;
    private $processId;
    private $request;
    private $response;
    private $lstLastId;

    /**
    * @param $database [database bağlantısı için]
    * @param $logType  [işlem yapılan api 1:Listeleme, 2:Detay, 3:Güncelleme]
    * @param $processId  [işlem Gören Orde id,2:Detay, 3:Güncelleme için]
    * @param $request  [yapılan istek]
    * @param $response  [dönen cevap]
    * @param $lstLastId  [Listelemede en büyük olarak gelen order id]
    */

    public function __construct($database,$logType,$request="",$response="",$processId=0,$lstLastId=0){
        $this->database = $database;
        $this->logType = $logType;
        $this->processId = $processId;
        $this->request = $request;
        $this->response = $response;
        $this->lstLastId = $lstLastId;
    }

    /**
     * Apilerde oluşan loglanma işlemleri 
     */
    
    public function insertLog(){
        $this->database->query('INSERT INTO logs (type,process_id,request,response,lstLastId,created_at) VALUES 
        (:type,:process_id,:request,:response,:lstLastId,NOW())');
        $this->database->bind(':type', $this->logType);
        $this->database->bind(':process_id', $this->processId);
        $this->database->bind(':request', $this->request);
        $this->database->bind(':response', $this->response);
        $this->database->bind(':lstLastId', $this->lstLastId);
        $this->database->execute();
        return $this->database->lastInsertId();
    }

    /**
     * En son çekilen listenin son order idsini almak için kullanılır 
     */
    public function lastListId(){
        $this->database->query('SELECT MAX(lstLastId) as maxId FROM logs WHERE type = 1');
        $rows = $this->database->getSingleRow();
        return $rows['maxId'];
    }

    /**
     * Listede gelip kayıt edilen en büyük id tutulması için güncelleme
     */
    
    public function updateLog($lstId, $id){
        $this->database->query('UPDATE logs SET lstLastId = :lstLastId WHERE id = :id ');
        $this->database->bind(':lstLastId', $lstId);
        $this->database->bind(':id', $id);
        $this->database->execute();
    }

}

?>