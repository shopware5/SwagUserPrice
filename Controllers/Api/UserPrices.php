<?php

class Shopware_Controllers_Api_UserPrices extends Shopware_Controllers_Api_Rest
{
    protected $resource = null;

	/* 
	 * Initalization
	 */
    public function init()
    {
        $this->resource = \Shopware\Components\Api\Manager::getresource('UserPrice');
    }

    /* 
	 * Action: GET
	 * URI: /api/userprices/
	 * Result: List	
	 */
    public function indexAction()
    {
        $limit  = $this->Request()->getParam('limit', 1000);
        $offset = $this->Request()->getParam('start', 0);
        $sort   = $this->Request()->getParam('sort', array());
        $filter = $this->Request()->getParam('filter', array());

        $result = $this->resource->getList($offset, $limit, $filter, $sort);

        $this->View()->assign($result);
        $this->View()->assign('success', true);
    }

    /* 
	 * Action: GET
	 * URI: /api/userprices/{id}
	 * Result: Single Item
	 */
    public function getAction()
    {
        $id = $this->Request()->getParam('id');		

		$userprice = $this->resource->getOne($id);

        $this->View()->assign('data', $userprice);
        $this->View()->assign('success', true);
    }
    
    /**
     * Action: POST
     * URI: /api/userprices
     */
    public function postAction()
    {
        $userprice = $this->resource->create($this->Request()->getPost());

        $location = $this->apiBaseUrl . 'userprices/' . $userprice->getId();
        $data = [
            'id' => $userprice->getId(),
            'location' => $location,
        ];

        $this->View()->assign(['success' => true, 'data' => $data]);
        $this->Response()->setHeader('Location', $location);
    }
    
    /**
     * Update user price
     *
     * PUT /api/userprices/{id}
     */
    public function putAction()
    {
        $id = $this->Request()->getParam('id');
        $params = $this->Request()->getPost();

        $userprice = $this->resource->update($id, $params);

        $location = $this->apiBaseUrl . 'userprices/' . $userprice->getId();
        $data = [
            'id' => $userprice->getId(),
            'location' => $location,
        ];

        $this->View()->assign(['success' => true, 'data' => $data]);
    }
    
    /**
     * Delete user price
     *
     * DELETE /api/userprices{id}
     */
    public function deleteAction()
    {
        $id = $this->Request()->getParam('id');
        $this->resource->delete($id);
        $this->View()->assign(['success' => true]);
    }
}
