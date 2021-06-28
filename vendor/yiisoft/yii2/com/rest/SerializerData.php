<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace yii\com\rest;

use Yii;
use yii\rest\Serializer;
use yii\data\Pagination;

class SerializerData extends Serializer
{

    /**
     * @return array the names of the requested fields. The first element is an array
     * representing the list of default fields requested, while the second element is
     * an array of the extra fields requested in addition to the default fields.
     * @see Model::fields()
     * @see Model::extraFields()
     */
    protected function getRequestedFields()
    {
        $fields = $this->request->get($this->fieldsParam);
        $expand = $this->request->get($this->expandParam);
        if ($expand !== null) {
            $expand = preg_split('/\s*,\s*/', $expand, -1, PREG_SPLIT_NO_EMPTY);
            $stmtExpand = [];
            foreach($expand as $exp) {
                $pos = strpos($exp, '(');
                if ($pos !== false) {
                    preg_match('/^\s*\(([a-zA-Z0-9\.\|]+)\)\s*/', substr($exp, $pos), $matches);
                    $exp = substr($exp, 0, $pos);
                    if (isset($matches[1])) {
                        $expandMixed = explode('|', $matches[1]);
                        if ($expandMixed[0]) {
                            $expandField = preg_split('/\s*\.\s*/', $expandMixed[0], -1, PREG_SPLIT_NO_EMPTY);
                            $getQueryParams = $this->request->get();
                            $getQueryParams = array_merge($getQueryParams, ["{$exp}_fields" => $expandField]);
                            $this->request->setQueryParams($getQueryParams);
                        }
                        if (isset($expandMixed[1])) {
                            $expandModel = preg_split('/\s*\.\s*/', $expandMixed[1], -1, PREG_SPLIT_NO_EMPTY);
                            $getQueryParams = $this->request->get();
                            $getQueryParams = array_merge($getQueryParams, ["{$exp}_expand" => $expandModel]);
                            $this->request->setQueryParams($getQueryParams);
                        }
                    }
                }
                $stmtExpand[] = $exp;
            }
            $expand = implode(',', $stmtExpand);unset($stmtExpand);
        }
        return [
            preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY),
            preg_split('/\s*,\s*/', $expand, -1, PREG_SPLIT_NO_EMPTY),
        ];
    }

    /**
     * Serializes a pagination into an array.
     * @param Pagination $pagination
     * @return array the array representation of the pagination
     * @see addPaginationHeaders()
     */
    protected function serializePagination($pagination)
    {
        return [
            $this->metaEnvelope => [
                'totalCount' => $pagination->totalCount,
                'pageCount' => $pagination->getPageCount(),
                'currentPage' => $pagination->getPage() + 1,
                'perPage' => $pagination->getPageSize(),
            ],
        ];
    }
}