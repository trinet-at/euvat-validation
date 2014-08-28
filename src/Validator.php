<?php
namespace Trinet\EuvatValidation;

/**
 * Class Validator
 * @package Trinet\EuvatValidation
 */
class Validator
{
    /**
     * URL to connect to
     */
    const WSDL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

    /**
     * @var bool
     */
    private $forceIpv4 = true;
    /**
     * @var array
     */
    private $options = array();

    /**
     * @var null|object
     */
    private $connection = null;

    /**
     * @var string|null
     */
    private $vat;

    private $validCcs = array(
        'AT',
        'BE',
        'BG',
        'CY',
        'CZ',
        'DE',
        'DK',
        'EE',
        'GR',
        'ES',
        'FI',
        'FR',
        'GB',
        'HR',
        'HU',
        'IE',
        'IT',
        'LT',
        'LU',
        'LV',
        'MT',
        'NL',
        'PL',
        'PT',
        'RO',
        'SE',
        'SI',
        'SK',
    );

    /**
     * @var bool
     */
    private $validResult = false;
    /**
     * @var array
     */
    private $returnData = array();

    /**
     * @param array $options
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($options = array())
    {
        if (!is_array($options)) {
            throw new \InvalidArgumentException('The `options` argument has to be an array');
        }

        if (!class_exists('SoapClient')) {
            throw new \RuntimeException('This package needs the php library `SoapClient`.');
        }

        foreach ($options as $option => $value) {
            $this->options[$option] = $value;
        }
    }

    public function connect()
    {
        if ($this->connection) {
            return;
        }

        $this->prepareStream();
        $this->connection = new \SoapClient(self::WSDL, $this->options);
    }

    /**
     * @param boolean $forceIpv4
     */
    public function setForceIpv4($forceIpv4)
    {
        $this->forceIpv4 = $forceIpv4;
    }

    /**
     * @return null|string
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param string $vat
     * @throws \InvalidArgumentException
     */
    public function setVat($vat)
    {
        $vat = strtoupper($vat);
        $checkCc = substr($vat, 0, 2);
        if (!in_array($checkCc, $this->validCcs)) {
            throw new \InvalidArgumentException('The entered VAT does not begin with a valid Country Code.');
        }

        $this->vat = $this->tidyVat($vat);
    }

    /**
     * @param null|string $vat
     * @throws \BadMethodCallException
     * @return bool
     */
    public function check($vat = null)
    {
        if (!$vat && !$this->getVat()) {
            throw new \BadMethodCallException('You have to set a VAT.');
        }

        if ($vat) {
            $this->setVat($vat);
        }

        if (!$this->connection) {
            $this->connect();
        }

        $result = $this->connection->checkVat(
            array(
                'countryCode' => $this->getVatCc(),
                'vatNumber' => $this->getVatNr()
            )
        );

        if ($result->valid) {
            $this->validResult = true;
            $this->returnData['name'] = $this->tidyString($result->name);
            $this->returnData['address'] = $this->tidyString($result->address);
            $this->returnData['requestDate'] = new \DateTime($this->tidyString($result->requestDate));
        } else {
            $this->validResult = false;
            $this->returnData = array();
        }

        #return $this->validResult;
        return $result;
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        if (empty($this->returnData['name'])) {
            return null;
        }
        return $this->returnData['name'];
    }

    /**
     * @return null|string
     */
    public function getAddress()
    {
        if (empty($this->returnData['address'])) {
            return null;
        }
        return $this->returnData['address'];
    }

    /**
     * @return null|\DateTime
     */
    public function getRequestDate()
    {
        if (empty($this->returnData['requestDate'])) {
            return null;
        }
        return $this->returnData['requestDate'];
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->validResult;
    }


    private function prepareStream()
    {
        if ($this->forceIpv4 === false) {
            return;
        }

        $streamOpts = array('socket' => array('bindto' => '0.0.0.0:0'));
        $streamContext = stream_context_create($streamOpts);

        $this->options['stream_context'] = $streamContext;
    }

    private function tidyVat($vat)
    {
        return str_replace(array(' ', '.', '-', ',', ', '), '', strtoupper(trim($vat)));
    }

    private function tidyString($str)
    {
        return trim($str);
    }

    private function getVatCc()
    {
        if (!$this->getVat()) {
            return null;
        }

        return substr($this->getVat(), 0, 2);
    }

    private function getVatNr()
    {
        if (!$this->getVat()) {
            return null;
        }

        return substr($this->getVat(), 2);
    }
}