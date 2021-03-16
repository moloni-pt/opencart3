<?php
/* Moloni
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace moloni;

class documents
{

    public function __construct(\moloni $moloni)
    {
        $this->moloni = $moloni;
    }

    public function getOne($input = array(), $company_id = false)
    {
        $values["company_id"] = $company_id ? $company_id : $this->moloni->company_id;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $values[$key] = $value;
            }
        } else {
            $values['document_id'] = $input;
        }

        $result = $this->moloni->connection->curl($this->moloni->documentType . "/getOne", $values);
        if (is_array($result) && isset($result['document_id'])) {
            return $result;
        } else {
            return false;
        }
    }

    public function getPDFLink($document_id, $company_id = false)
    {
        $values["company_id"] = $company_id ? $company_id : $this->moloni->company_id;
        $values["document_id"] = $document_id;

        $result = $this->moloni->connection->curl($this->moloni->documentType . "/getPDFLink", $values);
        if (is_array($result) && isset($result['url'])) {
            return $result['url'];
        } else {
            return false;
        }
    }

    public function getViewUrl($document_id, $status = 0)
    {
        switch ($this->moloni->documentType) {
            case "billsOfLading":
                return "GuiasTransporte/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "purchaseOrder":
                return "NotasEncomenda/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "deliveryNotes":
                return "GuiasRemessa/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "simplifiedInvoices":
                return "FaturaSimplificada/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "internalDocuments":
                return "DocumentosInternos/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "estimates":
                return "Orcamentos/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;

            case "invoices":
            case "FT":
                return "Faturas/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;
                break;

            case "invoiceReceipts":
            case "FR":
                return "FaturasRecibo/" . ($status == 0 ? "showUpdate" : "showDetail") . "/" . $document_id;
            default:
                break;
        }
    }

    public function insert($values = array(), $company_id = false)
    {
        $values["company_id"] = $company_id ? $company_id : $this->moloni->company_id;
        $values["plugin_id"] = "20";

        $result = $this->moloni->connection->curl($this->moloni->documentType . "/insert", $values);
        if (is_array($result) && isset($result['document_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao inserir documento", $result[0], __CLASS__ . "/" . __FUNCTION__, $result, $values);
            return false;
        }
    }

    public function update($values = array(), $company_id = false)
    {
        $values["company_id"] = $company_id ? $company_id : $this->moloni->company_id;

        $result = $this->moloni->connection->curl($this->moloni->documentType . "/update", $values);
        if (is_array($result) && isset($result['document_id'])) {
            return $result;
        } else {
            $this->moloni->errors->throwError("Erro ao actualizar documento", isset($result[0]) ? $result[0] : "", __CLASS__ . "/" . __FUNCTION__, $result, $values);
            return false;
        }
    }
}
