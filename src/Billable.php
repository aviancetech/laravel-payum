<?php

namespace Recca0120\LaravelPayum;

use Illuminate\Support\Str;
use Payum\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait Billable
{
    /**
     * authorize.
     *
     * @param  array  $options
     * @param  string $driver
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function authorize($options = [], $driver = null)
    {
        return $this->sendRequest('authorize', $options, $driver);
    }

    /**
     * capture.
     *
     * @param  array  $options
     * @param  string $driver
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function capture($options = [], $driver = null)
    {
        return $this->sendRequest('capture', $options, $driver);
    }

    /**
     * receive.
     *
     * @param string $payumToken
     * @param callable $callback
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($payumToken, callable $callback = null)
    {
        $status = $this->getPayumManager()->driver(null)->getStatus($payumToken);
        $payment = $status->getFirstModel();
        $token = $status->getToken();
        $driver = $token->getGatewayName();
        $method = sprintf('%s%s', 'receive', Str::studly($driver));

        $response = call_user_func_array([$this, $method], [$status, $payment, $driver]);

        if (is_null($callback) === false) {
            $callback($status, $payment, $driver);
        }

        return $response;
    }

    /**
     * payum.
     *
     * @param  array  $options
     * @param  string $driver
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function sendRequest($method, $options = [], $driver = null)
    {
        $gateway = $this->getPayumManager()->driver($driver);
        $driver = $gateway->driver();

        return new RedirectResponse(call_user_func_array([$gateway, $method], [function (PaymentInterface $payment) use ($method, $options, $driver) {
            return call_user_func_array(
                [$this, sprintf('%s%s', $method, Str::studly($driver))],
                [$payment, $options]
            );
        }]));
    }

    /**
     * getPayumManager.
     *
     * @return \Recca0120\LaravelPayum\PayumManager
     */
    protected function getPayumManager()
    {
        return app(PayumManager::class);
    }
}