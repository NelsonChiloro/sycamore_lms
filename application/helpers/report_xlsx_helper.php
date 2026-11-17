<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Small numeric report workbook; no legacy PHPExcel dependency. */
function report_xlsx_bytes(array $rows,$title,array $money_columns=array()) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('Excel export requires the PHP zip extension.');
    $xml=function($value) {
        $value=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',(string)$value);
        return htmlspecialchars($value,ENT_QUOTES|ENT_XML1,'UTF-8');
    };
    $sheet='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    foreach($rows as $index=>$row) {
        $number=$index+1;$sheet.='<row r="'.$number.'">';
        foreach(array_values($row) as $column=>$value) {
            $n=$column+1;$letter='';
            while($n>0) {$n--; $letter=chr(65+$n%26).$letter;$n=intdiv($n,26);}
            $ref=$letter.$number;
            if (is_int($value) || is_float($value)) {
                if (!is_finite((float)$value)) throw new RuntimeException('Invalid numeric report value.');
                $style=in_array($column,$money_columns,true)?' s="1"':'';
                $sheet.='<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>';
            } else $sheet.='<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$xml($value).'</t></is></c>';
        }
        $sheet.='</row>';
    }
    $sheet.='</sheetData></worksheet>';
    $path=tempnam(sys_get_temp_dir(),'syc_xlsx_');
    if ($path===false) throw new RuntimeException('Could not create the report file.');
    $zip=new ZipArchive();$opened=false;
    try {
        if ($zip->open($path,ZipArchive::OVERWRITE)!==true) throw new RuntimeException('Could not create Excel workbook.');
        $opened=true;
        $parts=array(
            '[Content_Types].xml'=>'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels'=>'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml'=>'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$xml($title).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels'=>'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml'=>'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml'=>$sheet
        );
        foreach($parts as $name=>$content) if (!$zip->addFromString($name,$content)) throw new RuntimeException('Excel file could not be written.');
        if (!$zip->close()) throw new RuntimeException('Excel file could not be finalized.');
        $opened=false;$bytes=file_get_contents($path);
        if ($bytes===false) throw new RuntimeException('Excel file could not be read.');
        return $bytes;
    } finally {if ($opened) $zip->close();if (is_file($path)) unlink($path);}
}
