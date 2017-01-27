<?php

/**
 * Collector for Cakephp 1.3 MySQLi info into DebugBar
 * @link http://phpdebugbar.com/docs/base-collectors.html#pdo DebugBar Docs
 */
class Mysqli_Collector extends DebugBar\DataCollector\DataCollector implements DebugBar\DataCollector\Renderable, DebugBar\DataCollector\AssetProvider
{
    
    private $connections;
    
    public function __construct() {
        $sources = ConnectionManager::sourceList();
        
        $connections = array();
        foreach ($sources as $source) {
            $db =& ConnectionManager::getDataSource($source);
            if (!$db->isInterfaceSupported('getLog')) {
                continue;
            } else {
                $db->fullDebug = true;
            }
            $connections[$source] = $db;
        }
        
        $this->connections = $connections;
    }
    
    /**
     * 
     * @return array
     */
    public function collect()
    {
        $data = array(
            'nb_statements' => 0,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0,
            'memory_usage' => 0,
            'peak_memory_usage' => 0,
            'statements' => array()
        );

        foreach ($this->connections as $source => $db) {
            $stmtData = $this->collectMysqli($db);
            $data['nb_statements'] += $stmtData['nb_statements'];
            $data['nb_failed_statements'] += $stmtData['nb_failed_statements'];
            $data['accumulated_duration'] += $stmtData['accumulated_duration'];
            $data['statements'] = array_merge($data['statements'],
                array_map(function ($s) use ($source) { $s['connection'] = $source; return $s; }, $stmtData['statements']));
        }

        $data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);

        return $data;
    }
    
    /**
     * Collects data from a single TraceablePDO instance
     *
     * @param TraceablePDO $pdo
     * @param TimeDataCollector $timeCollector
     * @return array
     */
    protected function collectMysqli(DboMysqli $mysqli)
    {
        $stmts = array();
        $durationTotal = 0;
        $statementCount = 0;
        $failedCount = 0;
        
        foreach ($mysqli->getLog() as $source => $logInfo) {
            
            if(is_array($logInfo)) {
            
                foreach ($logInfo as $k => $i) {
                    $stmts[] = array(
                        'sql' => $i['query'],
                        'row_count' => $i['numRows'],
                        'stmt_id' => ($k + 1),
                        'duration' => $i['took'],
                        'duration_str' => $this->getDataFormatter()->formatDuration($i['took']),
                        'is_success' => ($i['error']) ? false : true,
                        'error_code' => 0,
                        'error_message' => $i['error']
                    );
                    $durationTotal += $i['took'];
                    $failedCount += ($i['error']) ? 1 : 0;
                }
                $statementCount += count($logInfo);
            }
        }

        return array(
            'nb_statements' => $statementCount,
            'nb_failed_statements' => 0,
            'accumulated_duration' => $durationTotal,
            'accumulated_duration_str' => $this->getDataFormatter()->formatDuration($durationTotal),
            'statements' => $stmts
        );
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return 'mysqli';
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        return array(
            "database" => array(
                "icon" => "inbox",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "mysqli",
                "default" => "[]"
            ),
            "database:badge" => array(
                "map" => "mysqli.nb_statements",
                "default" => 0
            )
        );
    }

    /**
     * @return array
     */
    public function getAssets()
    {
        return array(
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js'
        );
    }
    
}