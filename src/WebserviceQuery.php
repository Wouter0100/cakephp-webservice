<?php

namespace Muffin\Webservice;

use Cake\Datasource\QueryTrait;
use Cake\Datasource\RepositoryInterface;
use Cake\Error\Debugger;
use Cake\Utility\Hash;
use Muffin\Webservice\Model\Endpoint;
use Muffin\Webservice\Webservice\WebserviceInterface;

class WebserviceQuery
{

    use QueryTrait;

    const ACTION_CREATE = 1;
    const ACTION_READ = 2;
    const ACTION_UPDATE = 3;
    const ACTION_DELETE = 4;

    /**
     * Indicates that the operation should append to the list
     *
     * @var int
     */
    const APPEND = 0;

    /**
     * Indicates that the operation should prepend to the list
     *
     * @var int
     */
    const PREPEND = 1;

    /**
     * Indicates that the operation should overwrite the list
     *
     * @var bool
     */
    const OVERWRITE = true;

    /**
     * Indicates whether internal state of this query was changed, this is used to
     * discard internal cached objects such as the transformed query or the reference
     * to the executed statement.
     *
     * @var bool
     */
    protected $_dirty = false;

    private $_action;
    private $_conditions = [];
    private $_page;
    private $_limit;
    private $_sort = [];

    /**
     * @var \Muffin\Webservice\Webservice\WebserviceInterface
     */
    protected $_webservice;

    /**
     * @var ResultSet
     */
    protected $_resultSet;

    public function __construct(WebserviceInterface $webservice, Endpoint $endpoint)
    {
        $this->_webservice = $webservice;
        $this->endpoint($endpoint);
    }

    public function create()
    {
        $this->action(self::ACTION_CREATE);

        return $this;
    }

    public function read()
    {
        $this->action(self::ACTION_READ);

        return $this;
    }

    public function update()
    {
        $this->action(self::ACTION_UPDATE);

        return $this;
    }

    public function delete()
    {
        $this->action(self::ACTION_DELETE);

        return $this;
    }

    /**
     * @param Endpoint|null $endpoint
     * @return Endpoint|$this
     */
    public function endpoint(Endpoint $endpoint = null)
    {
        if ($endpoint === null) {
            return $this->repository();
        }

        $this->repository($endpoint);

        return $this;
    }

    public function aliasField($field)
    {
        return [$field => $field];
    }

    /**
     * @param null $conditions
     * @param array $types
     * @param bool|false $overwrite
     *
     * @internal This method is only for compatibility with some plugins
     */
    public function where($conditions = null, $types = [], $overwrite = false)
    {
        $this->conditions($conditions, !$overwrite);
    }

    public function action($action = null)
    {
        if ($action === null) {
            return $this->_action;
        }

        $this->_action = $action;

        return $this;
    }

    public function conditions(array $conditions = null, $merge = true)
    {
        if ($conditions === null) {
            return $this->_conditions;
        }

        $this->_conditions = ($merge) ? Hash::merge($this->_conditions, $conditions) : $conditions;

        return $this;
    }

    public function page($page = null)
    {
        if ($page === null) {
            return $this->_page;
        }

        $this->_page = $page;

        return $this;
    }

    public function limit($limit = null)
    {
        if ($limit === null) {
            return $this->_limit;
        }

        $this->_limit = $limit;

        return $this;
    }

    public function sort(array $fields = null)
    {
        if ($fields === null) {
            return $this->_sort;
        }

        $this->_sort = $fields;

        return $this;
    }

    /**
     * Populates or adds parts to current query clauses using an array.
     * This is handy for passing all query clauses at once.
     *
     * @param array $options the options to be applied
     * @return $this This object
     */
    public function applyOptions(array $options)
    {
        if (isset($options['page'])) {
            $this->page($options['page']);

            unset($options['page']);
        }
        if (isset($options['limit'])) {
            $this->limit($options['limit']);

            unset($options['limit']);
        }
        if (isset($options['order'])) {
            $this->sort($options['order']);

            unset($options['order']);
        }

        $this->_options = Hash::merge($this->_options, $options);
    }

    public function count()
    {
        if (!$this->_resultSet) {
            $this->_execute();
        }

        return $this->_resultSet->total();
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if (!$this->_resultSet) {
            $this->limit(1);
        }

        return $this->all()->first();
    }


    /**
     * Executes this query and returns a traversable object containing the results
     *
     * @return \Traversable
     */
    protected function _execute()
    {
        return $this->_resultSet = $this->_webservice->execute($this, [
            'resourceClass' => $this->endpoint()->resourceClass()
        ]);
    }

    public function __debugInfo()
    {
        return [
            '(help)' => 'This is a Query object, to get the results execute or iterate it.',
            'action' => $this->action(),
            'page' => $this->page(),
            'limit' => $this->limit(),
            'sort' => $this->sort(),
            'extraOptions' => $this->getOptions(),
            'conditions' => $this->conditions(),
            'repository' => $this->endpoint(),
            'webservice' => $this->_webservice
        ];
    }
}
