<?php
namespace ptv;

class PTVHelper
{
    public function prepareRequest(string $phoneNumber)
    {
        return '<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
xmlns:soap="https://order208.mgts.corp.net/SoapOrder">
 <soapenv:Header/>
 <soapenv:Body>
 <soap:getPTV soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
 <phoneNum xsi:type="xsd:string">' . $phoneNumber . '</phoneNum>
 </soap:getPTV>
 </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Разбор ответа от PTV и преобразование к массиву
     * Вложенные поля приводятся к json формату
     *
     * @param string $response
     * @param bool $cutNulls
     * @return array
     * @throws \Exception
     */
    public function parseResponse(string $response, bool $cutNulls = false)
    {
        $reader = new \XMLReader();
        if (!$reader->XML($response)) {
            throw new \Exception("Incorrect XML format");
        }

        $ptvDataArray = [];
        $step = $reader->read();
        while ($step) {
            if ($reader->name == 'item' && !$reader->getAttribute('xsi:type')) {
                $itemNode = $reader->expand();
                $key = $this->readItemKey($itemNode);
                $value = @$this->readItemValue($itemNode);

                if (is_array($value)) {
                    $ptvDataArray[$key] = json_encode($value);
                } elseif ($cutNulls && !is_null($value)) {
                    $ptvDataArray[$key] = $value;
                } elseif (!$cutNulls) {
                    $ptvDataArray[$key] = $value;
                }

                $step = $reader->next();
            } else {
                $step = $reader->read();
            }
        }

        return $ptvDataArray;
    }

    /**
     * Прочитать имя поля элемента
     *
     * @param \DOMNode $item
     * @return string
     */
    protected function readItemKey(\DOMNode $item)
    {
        /** @var \DOMNode $keyNode */
        $keyNode = $item->childNodes[0];
        return (isset($keyNode->nodeValue) ? $keyNode->nodeValue : '');
    }

    /**
     * Прочитать значение поля элемента
     * Рекурсия. Если значение поля является массивом, то вызывается рекурсивный разбор
     *
     * @param \DOMNode $item
     * @return array|null|string
     */
    protected function readItemValue(\DOMNode $item)
    {
        /** @var \DOMNode $valueNode */
        $valueNode = $item->childNodes[1];
        /** @var \DOMAttr $type */
        $type = $valueNode->attributes['xsi:type'];
        if ($type->value == 'ns2:Map') {
            $values = [];
            /** @var \DOMNode $itemNode */
            foreach ($valueNode->childNodes as $itemNode) {
                $values[$this->readItemKey($itemNode)] = $this->readItemValue($itemNode);
            }

            return $values;
        }

        /** @var \DOMAttr $nil */
        $nil = $valueNode->attributes['xsi:nil'];
        if ($nil->value == 'true') {
            return null;
        }

        return $valueNode->nodeValue;
    }
}