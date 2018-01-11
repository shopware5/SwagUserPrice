<?php

class Shopware_Controllers_Api_UserPriceGroups extends Shopware_Controllers_Api_Rest
{
    protected $resource = null;

	/* 
	 * Initalization
	 */
    public function init()
    {
        $this->resource = \Shopware\Components\Api\Manager::getresource('UserPriceGroup');
    }

    /* 
	 * Action: GET
	 * URI: /api/userpricegroups/
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
	 * URI: /api/userpricegroups/{id}
	 * Result: Single Item
	 */
    public function getAction()
    {
        $id = $this->Request()->getParam('id');		

		$userpricegroup = $this->resource->getOne($id);

        $this->View()->assign('data', $userpricegroup);
        $this->View()->assign('success', true);
    }
    
    /**
     * Action: POST
     * URI: /api/userpricegroups
     */
    public function postAction()
    {
        $userpricegroup = $this->resource->create($this->Request()->getPost());

        $location = $this->apiBaseUrl . 'userpricegroups/' . $userpricegroup->getId();
        $data = [
            'id' => $userpricegroup->getId(),
            'location' => $location,
        ];

        $this->View()->assign(['success' => true, 'data' => $data]);
        $this->Response()->setHeader('Location', $location);
    }
        
    /**
     * Update user price group
     *
     * PUT /api/userpricegroups/{id}
     */
    public function putAction()
    {
        $id = $this->Request()->getParam('id');
        $params = $this->Request()->getPost();

        $userpricegroups = $this->resource->update($id, $params);

        $location = $this->apiBaseUrl . 'userpricegroups/' . $userpricegroups->getId();
        $data = [
            'id' => $userpricegroups->getId(),
            'location' => $location,
        ];

        $this->View()->assign(['success' => true, 'data' => $data]);
    }
    
    /**
     * Delete user price group
     *
     * DELETE /api/userpricegroups{id}
     */
    public function deleteAction()
    {
        $id = $this->Request()->getParam('id');
        $this->resource->delete($id);
        $this->View()->assign(['success' => true]);
    }
}
