<?php

namespace koma136\mongoyii\util;

use CPagination;

/**
 * EMongoPagination
 * @author Kim BeeJay <kim@beejay.ru>
 * corresponding to CPagination for MongoYii
 * @see yii/framework/web/CPagination
 */
class Pagination extends CPagination
{
	/**
	 * Applies LIMIT and SKIP to the specified query criteria.
	 * 
	 * @param EMongoCriteria $criteria the query criteria that should be applied with the limit
	 */
	public function applyLimit($criteria)
	{
		$criteria->limit = $this->getLimit();
		$criteria->skip = $this->getOffset();
	}
}
