<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SoapController extends Controller
{
    /**
     * Memproses Request SOAP Clearance Status Check (Minggu 9)
     */
    public function handle(Request $request)
    {
        $xmlContent = $request->getContent();
        
        // Ekstrak NIM dari payload XML request
        preg_match('/<nim_peminjam>(.*?)<\/nim_peminjam>/', $xmlContent, $matches);
        $nim = $matches[1] ?? '12S24031';

        $responseXml = '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:m="http://sipus.del.ac.id/soap">
    <soap:Body>
        <m:CheckClearanceStatusResponse>
            <m:nim_peminjam>' . htmlspecialchars($nim) . '</m:nim_peminjam>
            <m:is_cleared>true</m:is_cleared>
            <m:status_description>Bebas Pinjaman &amp; Denda Perpustakaan</m:status_description>
        </m:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>';

        return response($responseXml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Mengembalikan Dokumen WSDL SOAP
     */
    public function wsdl()
    {
        $wsdl = '<?xml version="1.0" encoding="UTF-8"?>
<definitions name="SIPUSService"
    targetNamespace="http://sipus.del.ac.id/soap"
    xmlns="http://schemas.xmlsoap.org/wsdl/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:tns="http://sipus.del.ac.id/soap"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <message name="CheckClearanceRequest">
        <part name="nim_peminjam" type="xsd:string"/>
    </message>
    <message name="CheckClearanceResponse">
        <part name="is_cleared" type="xsd:boolean"/>
    </message>
</definitions>';

        return response($wsdl, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }
}