<?php

/*
 * This file is part of the Aisel package.
 *
 * (c) Ivan Proskuryakov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aisel\OrderBundle\Manager;

use LogicException;
use Aisel\FrontendUserBundle\Entity\FrontendUser;
use Payum\Core\Request\Capture;
use Aisel\OrderBundle\Entity\Order;

/**
 * Manager for Orders, mostly used in REST API
 *
 * @author Ivan Proskoryakov <volgodark@gmail.com>
 */
class OrderManager
{
    protected $sc;
    protected $em;
    protected $settingsManager;
    protected $cartManager;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        $serviceContainer,
        $entityManager,
        $settingsManager,
        $cartManager
    )
    {
        $this->sc = $serviceContainer;
        $this->em = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->cartManager = $cartManager;
    }

    /**
     * Get cart Manager
     *
     * @return \Aisel\CartBundle\Manager\CartManager
     */
    public function getCartManager()
    {
        return $this->cartManager;
    }

    /**
     * Currency code from the system settings
     *
     * @param string $locale
     *
     * @return string $currency
     */
    private function getCurrencyCode($locale)
    {
        $config = $this
            ->settingsManager
            ->getConfigForEntity($locale, 'config_general');

        return $config['currency'];
    }

    /**
     * Get single order by given userId and orderId
     *
     * @param FrontendUser $user
     * @param int $orderId
     *
     * @return Order $orderDetails
     */
    public function getUserOrder($user, $orderId)
    {
        $order = $this->em
            ->getRepository('AiselOrderBundle:Order')
            ->findOrderForUser($user, $orderId);

        return $order;
    }

    /**
     * Get all order for user
     *
     * @param FrontendUser $user
     *
     * @throws LogicException
     *
     * @return Order $orderDetails
     */
    public function getUserOrders($user)
    {
        if (!($user)) throw new LogicException('User object is missing');

        $orders = $this->em
            ->getRepository('AiselOrderBundle:Order')
            ->findAllOrdersForUser($user);

        return $orders;
    }

    /**
     * Create order for given userId
     *
     * @param FrontendUser $user
     * @param mixed $orderInfo
     *
     * @throws LogicException
     *
     * @return Order $order
     */
    public function createOrderFromCart($user, $orderInfo)
    {
        if (!($user)) throw new LogicException('User object is missing');
        if (count($user->getCart()) == 0) return false;

        $order = $this->em
            ->getRepository('AiselOrderBundle:Order')
            ->createOrderFromCartForUser(
                $user,
                $this->getCurrencyCode($orderInfo['locale']),
                $orderInfo
            );

        $token = $this->sc->get('payum.security.token_factory')->createCaptureToken(
            $orderInfo['payment_method'],
            $order,
            'aisel_payum_order'
        );
        $token->getTargetUrl();

//        $payment = $this->sc->get('payum')->getPayment('offline');
//        $payment->execute(new Capture($order));
        return $order;
    }

    /**
     * Create order for user
     *
     * @param FrontendUser $user
     * @param array $products
     * @param mixed $orderInfo
     *
     * @throws LogicException
     *
     * @return Order $orderDetails
     */
    public function createOrderFromProducts($user, $products, $orderInfo)
    {
        if (!($user)) {
            throw new LogicException('User object is missing');
        }

        $order = $this
            ->em
            ->getRepository('AiselOrderBundle:Order')
            ->createOrderFromProductsForUser(
                $user,
                $products,
                $this->getCurrencyCode($orderInfo['locale']),
                $orderInfo
            );

        return $order;
    }

    /**
     * Get list of all pages
     *
     * @param array $params
     *
     * @return array
     */
    public function getCollection($params)
    {
        $total = $this->em->getRepository('AiselOrderBundle:Order')->getTotalFromRequest($params);
        $collection = $this->em->getRepository('AiselOrderBundle:Order')->getCollectionFromRequest($params);
        $return = array(
            'total' => $total,
            'collection' => $collection
        );

        return $return;
    }

    /**
     * Get single detailed page with category by ID
     *
     * @param int $id
     *
     * @return \Aisel\PageBundle\Entity\Page $pageDetails
     */
    public function getItem($id)
    {
        $order = $this->em->getRepository('AiselOrderBundle:Order')->find($id);

        return $order;
    }

}
