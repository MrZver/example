<?php

namespace Boodmo\Sales\Service;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use OpsWay\ZohoBooks\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FakeZohoClient extends Client
{
    const LOG_PATH = __DIR__ . '/../../../../../data/logs/fakezoho.log';

    public static $sortCount = 0;

    public static $history = [];

    public function __construct($options)
    {
        $this->authToken = $options['auth_token'] ?? '';
        $this->logging('START REQUEST: ' . $_SERVER['REQUEST_URI']);
        $mock = new MockHandler(iterator_to_array($this->generateResponse()));
        $handler = HandlerStack::create($mock);
        $this->httpClient = new \GuzzleHttp\Client(['base_uri' => self::ENDPOINT, 'handler' => $handler]);
    }

    public function getList($url, $organizationId, array $filters)
    {
        $this->logging('ZOHOAPI: GET '. $url, array_merge($this->getParams($organizationId), $filters));
        return parent::getList($url, $organizationId, $filters);
    }

    public function get($url, $organizationId, $id, array $params = [])
    {
        $this->logging('ZOHOAPI: GET '. $url . '/' . $id, $this->getParams($organizationId));
        return parent::get($url, $organizationId, $id);
    }

    public function post($url, $organizationId, array $data = [], array $params = [])
    {
        $this->logging('ZOHOAPI: POST '. $url, $this->getParams($organizationId, $data));
        return parent::post($url, $organizationId, $data);
    }

    public function put($url, $organizationId, $id, array $data = [], array $params = [])
    {
        $this->logging('ZOHOAPI: PUT '. $url . '/'. $id, $this->getParams($organizationId, $data));
        return parent::put($url, $organizationId, $id, $data);
    }

    public function delete($url, $organizationId, $id)
    {
        $this->logging('ZOHOAPI: DELETE '. $url . '/' . $id, $this->getParams($organizationId));
        return parent::delete($url, $organizationId, $id);
    }

    protected function generateResponse()
    {
        for ($i = 0; $i < 50; $i++) {
            yield new Response(
                200,
                ['Content-Type' => 'application/json;charset=UTF-8'],
                \GuzzleHttp\json_encode($this->stubResponse())
            );
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        //foreach (self::$history as $transaction) {
            /**
             * @var $request \GuzzleHttp\Psr7\Request
             */
        //    $request = $transaction['request'];
        //    $this->logging('API: ' . $request->getMethod() . ' ' . $request->getUri(), $request->getBody());
        //}
        $this->logging('END REQUEST: ' . $_SERVER['REQUEST_URI']);
    }

    private function stubResponse()
    {
        return [
            'code'          => 0,
            'message'       => 'Success',
            'bill'          => [
                'bill_id'    => 'fake_bill_id_000',
                'bill_number'    => 'fake_bill_number_000',
                'line_items' => [
                    [
                        'rate' => 0,
                    ]
                ]
            ],
            'bills'         => [
                [
                    'bill_id' => 'fake_zoho_id_000',
                ]
            ],
            'contact' => [
                'contact_id' => 'fake_contact_id_000',
                'place_of_contact' => 'fake_place_contact_000'
            ],
            'invoice' => [
                'invoice_id' => 'fake_invoice_id_000',
            ],
            'invoices' => [
                [
                    'invoice_id' => 'fake_invoice_id_000',
                ]
            ],
            'vendor_credit' => [
                'vendor_credit_id' => 'fake_credit_id_000',
                'total' => '1000',
            ],
            'vendor_credits' => [
                [
                    'vendor_credit_id' => 'fake_credit_id_000',
                    'total' => '1000',
                ],
            ],
            'payment' => [
                'payment_id' => 'fake_payment_id_000',
                'unused_amount' => 1000,
            ],
            'vendorpayment' => [
                'payment_id' => 'fake_payment_id_000',
            ],
            'journal' => [
                'journal_id' => 'fake_journal_id_000',
            ],
            'page_context' => [
                
            ],
            'creditnote' => [
                'creditnote_id' => 'fake_creditnote_id_000'
            ],
        ];
    }

    public static function logging(string $message, $data = '')
    {
        file_put_contents(
            self::LOG_PATH,
            '#' . self::$sortCount++ . ' - ' . $message . "\n PARAMS: " . print_r($data, true) . "\n\n",
            FILE_APPEND
        );
    }

    public static function clearLog()
    {
        unlink(self::LOG_PATH);
    }

    public static function outputLog()
    {
        return @file_get_contents(self::LOG_PATH);
    }
}
