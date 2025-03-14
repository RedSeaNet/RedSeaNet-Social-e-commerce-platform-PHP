<?php

namespace Redseanet\Customer\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Wishlist as Model;
use Redseanet\Customer\Model\Wishlist\Item as WishlistItem;
use Redseanet\Customer\Model\Collection\Wishlist as CollectionWishlist;
use Redseanet\Customer\Model\Collection\Wishlist\Item as CollectionWishlistItem;
use Redseanet\Lib\Bootstrap;

class Wishlist extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param array $data
     * @param int $languageId
     * @return array
     */
    public function addWishlistItem($id, $token, $customerId, $data = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }

        $wishlist = new Model();
        $wishlist->load($customerId, 'customer_id');
        if (!$wishlist->getId()) {
            $wishlist->load($wishlist->getId())->setData(['customer_id' => $customerId, 'id' => null])->save();
        }
        $collectionWishlistItem = new CollectionWishlistItem();
        if (empty($data['options'])) {
            //$collectionWishlistItem->where("wishlist_id=?",$wishlist->getId());
            $collectionWishlistItem->where('wishlist_id=' . $wishlist->getId() . " and (options='' or options is null)");
        } else {
            $collectionWishlistItem->where(['wishlist_id' => $wishlist->getId(), 'options' => $data['options']]);
        }
        //echo $collectionWishlistItem->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $collectionWishlistItem->load(true, true);

        //print_r($collectionWishlistItem);
        if (count($collectionWishlistItem) > 0) {
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'the product is already in your wishlist'];
            return $this->responseData;
        } else {
            //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($data)));
            $pushData = $data + ['wishlist_id' => $wishlist->getId()];
            //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($pushData)));
            $wishlist->addItem($pushData);
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'add wish list successfully'];
            return $this->responseData;
        }
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param int $page
     * @param int $limit
     * @param int $languageId
     * @return array
     */
    public function getWishlist($id, $token, $customerId, $page = 1, $limit = 20, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $wishlist = new CollectionWishlist();
        $wishlist->join('wishlist_item', 'wishlist_item.wishlist_id=wishlist.id');
        $wishlist->where(['wishlist.customer_id' => intval($customerId)]);
        $wishlist->limit((int) $limit)->offset(($page > 0 ? ($page - 1) : 0) * $limit);
        $wishlist->order('wishlist_item.added_at DESC');
        $wishlist->load(true, true);
        $resultData = [];
        for ($i = 0; $i < count($wishlist); $i++) {
            $image = '';
            if ($wishlist[$i]['image'] != '') {
                $image = $this->getResourceUrl('image/' . $wishlist[$i]['image']);
            } else {
                $image = $this->getPubUrl('frontend/images/placeholder.png');
            }
            unset($wishlist[$i]['image']);
            $resultData[] = $wishlist[$i] + ['image' => $image];
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get wish list information successfully'];
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param inat $itemId
     * @param int $page
     * @param int $limit
     * @param int $languageId
     * @return array
     */
    public function deleteWishlistItem($id, $token, $customerId, $itemId, $page = 1, $limit = 20, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $wishlistItem = new WishlistItem();
        $wishlistItem->setId(intval($itemId))->remove();

        $deleteWishlist = new CollectionWishlist();
        $deleteWishlist->join('wishlist_item', 'wishlist_item.wishlist_id=wishlist.id');
        $deleteWishlist->where('wishlist.customer_id=' . intval($customerId));
        $deleteWishlist->limit((int) $limit)->offset(($page > 0 ? ($page - 1) : 0) * $limit);
        $deleteWishlist->order('wishlist_item.added_at DESC');
        $deleteWishlist->load(true, true);
        $resultData = [];
        for ($i = 0; $i < count($deleteWishlist); $i++) {
            $image = '';
            if ($deleteWishlist[$i]['image'] != '') {
                $image = $this->getResourceUrl('image/' . $deleteWishlist[$i]['image']);
            }
            unset($deleteWishlist[$i]['image']);
            $resultData[] = $deleteWishlist[$i] + ['image' => $image];
        }

        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'delete wish list item successfully'];
        return $this->responseData;
    }

}
