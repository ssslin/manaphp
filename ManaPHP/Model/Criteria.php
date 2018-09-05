<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\RuntimeException;

/**
 * Class ManaPHP\Model\Criteria
 *
 * @package ManaPHP\Model
 * @property \ManaPHP\Http\RequestInterface $request
 */
abstract class Criteria extends Component implements CriteriaInterface
{
    /**
     * @var \ManaPHP\Model
     */
    protected $_model;

    /**
     * @var bool
     */
    protected $_multiple;

    /**
     * @var array
     */
    protected $_with = [];

    /**
     * @var string|callable
     */
    protected $_index;

    /**
     * @return \ManaPHP\Model
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Sets SELECT DISTINCT / SELECT ALL flag
     *
     * @param bool $distinct
     *
     * @return static
     */
    public function distinct($distinct = true)
    {
        throw new NotImplementedException(__METHOD__);
    }

    /**
     * @param string $field
     * @param array  $value
     *
     * @return array
     */
    protected function _normalizeTimeBetween($field, $value)
    {
        $left = $value[0];
        if ($left && is_string($left)) {
            $left = strtotime($left[0] === '-' || $left[0] === '+' ? date('Y-m-d', strtotime($left)) : $left);
        }

        $right = $value[1];
        if ($right && is_string($right)) {
            $right = strtotime($right[0] === '-' || $right[0] === '+' ? date('Y-m-d 23:59:59', strtotime($right)) : $right);
        }

        if ($format = $this->_model->getDateFormat($field)) {
            return [$left ? date($format, $left) : null, $right ? date($format, $right) : null];
        } else {
            return [$left ?: null, $right ?: null];
        }
    }

    /**
     * @param array $filters
     *
     * @return static
     */
    public function whereSearch($filters)
    {
        $data = $this->request->get();

        $conditions = [];
        $fields = $this->_model->getFields();
        foreach ($filters as $k => $v) {
            preg_match('#^(\w+)(.*)$#', is_int($k) ? $v : $k, $match);
            $field = $match[1];

            if (!in_array($field, $fields, true)) {
                throw new InvalidValueException(['`:model` is not contains `:field` field', 'model' => get_declared_classes(), 'field' => $field]);
            }

            if (is_int($k)) {
                if (!isset($data[$field])) {
                    continue;
                }
                $value = $data[$field];
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }
                }
                $conditions[$v] = $value;
            } else {
                $conditions[$k] = $v;
            }
        }

        return $this;
    }

    /**
     * @param string     $field
     * @param int|string $min
     * @param int|string $max
     *
     * @return static
     */
    public function whereDateBetween($field, $min, $max)
    {
        if ($format = $this->_model->getDateFormat($field)) {
            if ($min) {
                if (is_numeric($min)) {
                    $min = date($format, $min);
                } elseif (preg_match('#^[\d-/:]+$#', $min) !== 1) {
                    $min = date($format, strtotime($min));
                }
            }

            if ($max) {
                if (is_numeric($max)) {
                    $max = date($format, $max);
                } elseif (preg_match('#^[\d-/:]+$#', $max) !== 1) {
                    $max = date($format, strtotime($max));
                }
            }
        } else {
            if ($min && !is_numeric($min)) {
                $min = (int)strtotime($min);
            }
            if ($max && !is_numeric($max)) {
                $max = (int)strtotime($max);
            }
        }

        return $this->whereBetween($field, $min ?: null, $max ?: null);
    }

    /**
     * alias of whereBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function betweenWhere($expr, $min, $max)
    {
        return $this->whereBetween($expr, $min, $max);
    }

    /**
     * alias of whereNotBetween
     *
     * @param string           $expr
     * @param int|float|string $min
     * @param int|float|string $max
     *
     * @return static
     * @deprecated
     */
    public function notBetweenWhere($expr, $min, $max)
    {
        return $this->whereNotBetween($expr, $min, $max);
    }

    /**
     * alias of whereIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     */
    public function inWhere($expr, $values)
    {
        return $this->whereIn($expr, $values);
    }

    /**
     * alias of whereNotIn
     *
     * @param string                           $expr
     * @param array|\ManaPHP\Db\QueryInterface $values
     *
     * @return static
     * @deprecated
     */
    public function notInWhere($expr, $values)
    {
        return $this->whereNotIn($expr, $values);
    }

    /**
     * @param string|array $with
     *
     * @return static
     */
    public function with($with)
    {
        if (is_string($with)) {
            $this->_with[] = $with;
        } else {
            $this->_with = array_merge($this->_with, $with);
        }

        return $this;
    }

    /**
     * @param int $size
     * @param int $page
     *
     * @return static
     */
    public function page($size = null, $page = null)
    {
        if ($size === null) {
            $size = $this->request->get('size', 'int', 10);
        }

        if ($page === null) {
            $page = $this->request->get('page', 'int', 1);
        }

        $this->limit($size, ($page - 1) * $size);

        return $this;
    }

    /**
     * @param bool $multiple
     *
     * @return static
     */
    public function setFetchType($multiple)
    {
        $this->_multiple = $multiple;

        return $this;
    }

    /**
     * @return \ManaPHP\Model[]|\ManaPHP\Model|false
     */
    public function fetch()
    {
        if ($this->_multiple === true) {
            return $this->fetchAll();
        } elseif ($this->_multiple === false) {
            return $this->fetchOne();
        } else {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new RuntimeException('xxx');
        }
    }

    /**
     * @param string $function
     * @param string $alias
     * @param string $field
     *
     * @return mixed
     */
    protected function _groupResult($function, $alias, $field)
    {
        $r = $this->aggregate([$alias => "$function($field)"]);
        return isset($r[0]) ? $r[0][$alias] : 0;
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function sum($field)
    {
        return $this->_groupResult('SUM', 'summary', $field);
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function max($field)
    {
        return $this->_groupResult('MAX', 'maximum', $field);
    }

    /**
     * @param string $field
     *
     * @return int|float
     */
    public function min($field)
    {
        return $this->_groupResult('MIN', 'minimum', $field);
    }

    /**
     * @param string $field
     *
     * @return double
     */
    public function avg($field)
    {
        return (double)$this->_groupResult('AVG', 'average', $field);
    }

    /**
     * @param string $field
     *
     * @return int
     */
    public function count($field = null)
    {
        $r = $this->_groupResult('COUNT', 'row_count', $field ?: '*');
        if (is_string($r)) {
            $r = (int)$r;
        }

        return $r;
    }

    public function jsonSerialize()
    {
        return $this->fetch();
    }

    /**
     * @param array $options
     *
     * @return static
     */
    public function options($options)
    {
        if (!$options) {
            return $this;
        }

        if (isset($options['limit'])) {
            $this->limit($options['limit'], isset($options['offset']) ? $options['offset'] : 0);
        } elseif (isset($options['size'])) {
            $this->page($options['size'], isset($options['page']) ? $options['page'] : null);
        }

        if (isset($options['distinct'])) {
            $this->distinct($options['distinct']);
        }

        if (isset($options['order'])) {
            $this->orderBy($options['order']);
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Model $instance
     */
    protected function _with($instance)
    {
        foreach ($this->_with as $k => $v) {
            $method = 'get' . ucfirst(is_string($k) ? $k : $v);

            if (is_int($k)) {
                $data = $instance->$method()->fetch();
            } elseif (is_string($v)) {
                $data = $instance->$method()->select(preg_split('#[\s,]+#', $v, -1, PREG_SPLIT_NO_EMPTY))->fetch();
            } elseif (is_array($v)) {
                $data = $instance->$method()->select($v)->fetch();
            } elseif (is_callable($v)) {
                $data = $v($instance->$method());
            } else {
                throw new InvalidValueException(['`:with` with is invalid', 'with' => $k]);
            }

            if ($data instanceof self) {
                $data = $data->fetch();
            }

            $instance->{is_string($k) ? $k : $v} = $data;
        }
    }

    /**
     * @return \ManaPHP\Model|null
     */
    public function fetchOne()
    {
        $modelName = get_class($this->_model);

        if ($r = $this->limit(1)->execute()) {
            $model = new $modelName($r[0]);
            if ($this->_with) {
                $this->_with($model);
            }
            return $model;
        } else {
            return null;
        }
    }

    /**
     * @return \ManaPHP\Model[]
     */
    public function fetchAll()
    {
        $modelName = get_class($this->_model);

        $models = [];
        foreach ($this->execute() as $k => $result) {
            $model = new $modelName($result);
            if ($this->_with) {
                $this->_with($model);
            }

            $models[$k] = $model;
        }

        return $models;
    }
}