<?php

/**
 * Classe Mensagem
 */
class Mensagem extends Model {

    protected static $useTable = "mensagem";
    
    private $sequencial;
    private $sinal;
    private $texto;
    
    function Mensagem($data = NULL) {
        if ($data != NULL) {
            parent::__construct(isset($data['id']) ? (int) $data['id'] : NULL);
            $this->sequencial = isset($data["sequencial"]) && $data["sequencial"] != "" ? (int) $data["sequencial"] : NULL;
            $this->sinal = isset($data["sinal"]) && $data["sinal"] != "" ? $data["sinal"] : NULL;
            $this->texto = isset($data["texto"]) && $data["texto"] != "" ? $data["texto"] : NULL;
            
        } 
        else
            parent::__construct();
    }

    public function toArray() {
        return get_object_vars($this);
    }

}