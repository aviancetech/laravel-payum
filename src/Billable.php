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
        return $this->payum('authorize', $options, $driver);
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
        return $this->payum('capture', $options, $driver);
    }

    /**
     * done.
     *
     * @param string $payumToken
     * @param callable $callback
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function done($payumToken, callable $callback = null)
    {
        $payumDecorator = $this->getPayumDecorator();
        $status = $payumDecorator->getStatus($payumToken);
        $payment = $status->getFirstModel();
        $token = $status->getToken();
        $gatewayName = $token->getGatewayName();
        $method = sprintf('%s%s', 'done', Str::studly($gatewayName));

        $response = call_user_func_array([$this, $method], [$status, $payment, $gatewayName]);

        if (is_callable($callback) === true) {
            $callback($status, $payment, $gatewayName);
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
    protected function payum($method, $options = [], $driver = null)
    {
        $payumDecorator = $this->getPayumDecorator($driver);
        $driver = $payumDecorator->driver();

        return new RedirectResponse(call_user_func_array([$payumDecorator, $method], [function (PaymentInterface $payment) use ($method, $options, $driver) {
            $method = sprintf('%s%s', $method, Str::studly($driver));

            return call_user_func_array([$this, $method], [$payment, $options]);
        }]));
    }

    /**
     * getPayumDecorator.
     *
     * @param  string $driver
     * @return \Recca0120\LaravelPayum\PayumDecorator
     */
    protected function getPayumDecorator($driver = null)
    {
        return $this->getPayumManager()->driver($driver);
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
