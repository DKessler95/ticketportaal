<?php
/**
 * Report Class
 * 
 * Handles analytics and reporting operations for the ticket system
 * Provides methods for ticket volume, resolution time, agent performance, and satisfaction metrics
 */

require_once __DIR__ . '/Database.php';

class Report {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get ticket volume by period (day/week/month)
     * 
     * @param string $period Period type: 'day', 'week', or 'month'
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array|false Array of ticket counts by period
     */
    public function getTicketVolumeByPeriod($period = 'day', $startDate = null, $endDate = null) {
        try {
            // Set default date range if not provided (last 30 days)
            if ($startDate === null) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if ($endDate === null) {
                $endDate = date('Y-m-d');
            }
            
            // Determine the date format based on period
            $dateFormat = match($period) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-%u',
                'month' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            
            $sql = "SELECT 
                        DATE_FORMAT(created_at, ?) as period,
                        COUNT(*) as ticket_count,
                        COUNT(CASE WHEN status = 'open' THEN 1 END) as open_count,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
                        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count,
                        COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count
                    FROM tickets
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY period
                    ORDER BY period ASC";
            
            return $this->db->fetchAll($sql, [$dateFormat, $startDate, $endDate]);
        } catch (Exception $e) {
            $this->logError('Report', 'Failed to get ticket volume by period', [
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get average resolution time by category
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array|false Array of categories with average resolution times
     */
    public function getAverageResolutionTimeByCategory($startDate = null, $endDate = null) {
        try {
            // Set default date range if not provided (last 30 days)
            if ($startDate === null) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if ($endDate === null) {
                $endDate = date('Y-m-d');
            }
            
            $sql = "SELECT 
                        c.name as category_name,
                        c.sla_hours,
                        COUNT(t.ticket_id) as total_tickets,
                        COUNT(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 END) as resolved_tickets,
                        AVG(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) 
                        END) as avg_resolution_hours,
                        MIN(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) 
                        END) as min_resolution_hours,
                        MAX(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) 
                        END) as max_resolution_hours,
                        COUNT(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            AND TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) <= c.sla_hours 
                            THEN 1 
                        END) as within_sla_count
                    FROM categories c
                    LEFT JOIN tickets t ON c.category_id = t.category_id
                        AND DATE(t.created_at) BETWEEN ? AND ?
                    GROUP BY c.category_id, c.name, c.sla_hours
                    ORDER BY c.name ASC";
            
            $results = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            // Calculate SLA compliance percentage
            if ($results) {
                foreach ($results as &$row) {
                    $row['sla_compliance_percentage'] = $row['resolved_tickets'] > 0 
                        ? round(($row['within_sla_count'] / $row['resolved_tickets']) * 100, 2)
                        : 0;
                }
            }
            
            return $results;
        } catch (Exception $e) {
            $this->logError('Report', 'Failed to get average resolution time by category', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get agent performance metrics
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array|false Array of agent performance data
     */
    public function getAgentPerformance($startDate = null, $endDate = null) {
        try {
            // Set default date range if not provided (last 30 days)
            if ($startDate === null) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if ($endDate === null) {
                $endDate = date('Y-m-d');
            }
            
            $sql = "SELECT 
                        u.user_id,
                        CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                        u.email,
                        COUNT(t.ticket_id) as total_assigned,
                        COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress_count,
                        COUNT(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 END) as resolved_count,
                        AVG(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) 
                        END) as avg_resolution_hours,
                        AVG(CASE 
                            WHEN t.satisfaction_rating IS NOT NULL 
                            THEN t.satisfaction_rating 
                        END) as avg_satisfaction_rating,
                        COUNT(CASE WHEN t.satisfaction_rating IS NOT NULL THEN 1 END) as rated_tickets
                    FROM users u
                    LEFT JOIN tickets t ON u.user_id = t.assigned_agent_id
                        AND DATE(t.created_at) BETWEEN ? AND ?
                    WHERE u.role IN ('agent', 'admin')
                    GROUP BY u.user_id, u.first_name, u.last_name, u.email
                    HAVING total_assigned > 0
                    ORDER BY resolved_count DESC, avg_resolution_hours ASC";
            
            return $this->db->fetchAll($sql, [$startDate, $endDate]);
        } catch (Exception $e) {
            $this->logError('Report', 'Failed to get agent performance', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get satisfaction metrics
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array|false Array with satisfaction metrics
     */
    public function getSatisfactionMetrics($startDate = null, $endDate = null) {
        try {
            // Set default date range if not provided (last 30 days)
            if ($startDate === null) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if ($endDate === null) {
                $endDate = date('Y-m-d');
            }
            
            $sql = "SELECT 
                        COUNT(CASE WHEN satisfaction_rating IS NOT NULL THEN 1 END) as total_ratings,
                        AVG(satisfaction_rating) as avg_rating,
                        COUNT(CASE WHEN satisfaction_rating = 1 THEN 1 END) as rating_1_count,
                        COUNT(CASE WHEN satisfaction_rating = 2 THEN 1 END) as rating_2_count,
                        COUNT(CASE WHEN satisfaction_rating = 3 THEN 1 END) as rating_3_count,
                        COUNT(CASE WHEN satisfaction_rating = 4 THEN 1 END) as rating_4_count,
                        COUNT(CASE WHEN satisfaction_rating = 5 THEN 1 END) as rating_5_count,
                        COUNT(CASE WHEN status IN ('resolved', 'closed') THEN 1 END) as total_resolved
                    FROM tickets
                    WHERE DATE(created_at) BETWEEN ? AND ?";
            
            $result = $this->db->fetchOne($sql, [$startDate, $endDate]);
            
            if ($result) {
                // Calculate rating distribution percentages
                $totalRatings = $result['total_ratings'];
                if ($totalRatings > 0) {
                    $result['rating_1_percentage'] = round(($result['rating_1_count'] / $totalRatings) * 100, 2);
                    $result['rating_2_percentage'] = round(($result['rating_2_count'] / $totalRatings) * 100, 2);
                    $result['rating_3_percentage'] = round(($result['rating_3_count'] / $totalRatings) * 100, 2);
                    $result['rating_4_percentage'] = round(($result['rating_4_count'] / $totalRatings) * 100, 2);
                    $result['rating_5_percentage'] = round(($result['rating_5_count'] / $totalRatings) * 100, 2);
                } else {
                    $result['rating_1_percentage'] = 0;
                    $result['rating_2_percentage'] = 0;
                    $result['rating_3_percentage'] = 0;
                    $result['rating_4_percentage'] = 0;
                    $result['rating_5_percentage'] = 0;
                }
                
                // Calculate response rate
                $result['response_rate'] = $result['total_resolved'] > 0 
                    ? round(($totalRatings / $result['total_resolved']) * 100, 2)
                    : 0;
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logError('Report', 'Failed to get satisfaction metrics', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get category analysis
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return array|false Array of category statistics
     */
    public function getCategoryAnalysis($startDate = null, $endDate = null) {
        try {
            // Set default date range if not provided (last 30 days)
            if ($startDate === null) {
                $startDate = date('Y-m-d', strtotime('-30 days'));
            }
            if ($endDate === null) {
                $endDate = date('Y-m-d');
            }
            
            $sql = "SELECT 
                        c.name as category_name,
                        c.default_priority,
                        c.sla_hours,
                        COUNT(t.ticket_id) as total_tickets,
                        COUNT(CASE WHEN t.status = 'open' THEN 1 END) as open_tickets,
                        COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress_tickets,
                        COUNT(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 END) as resolved_tickets,
                        COUNT(CASE WHEN t.priority = 'urgent' THEN 1 END) as urgent_count,
                        COUNT(CASE WHEN t.priority = 'high' THEN 1 END) as high_count,
                        COUNT(CASE WHEN t.priority = 'medium' THEN 1 END) as medium_count,
                        COUNT(CASE WHEN t.priority = 'low' THEN 1 END) as low_count,
                        AVG(CASE 
                            WHEN t.resolved_at IS NOT NULL 
                            THEN TIMESTAMPDIFF(HOUR, t.created_at, t.resolved_at) 
                        END) as avg_resolution_hours,
                        AVG(CASE 
                            WHEN t.satisfaction_rating IS NOT NULL 
                            THEN t.satisfaction_rating 
                        END) as avg_satisfaction
                    FROM categories c
                    LEFT JOIN tickets t ON c.category_id = t.category_id
                        AND DATE(t.created_at) BETWEEN ? AND ?
                    GROUP BY c.category_id, c.name, c.default_priority, c.sla_hours
                    ORDER BY total_tickets DESC";
            
            $results = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            // Calculate percentages
            if ($results) {
                $totalTickets = array_sum(array_column($results, 'total_tickets'));
                
                foreach ($results as &$row) {
                    $row['percentage_of_total'] = $totalTickets > 0 
                        ? round(($row['total_tickets'] / $totalTickets) * 100, 2)
                        : 0;
                    
                    $row['resolution_rate'] = $row['total_tickets'] > 0 
                        ? round(($row['resolved_tickets'] / $row['total_tickets']) * 100, 2)
                        : 0;
                }
            }
            
            return $results;
        } catch (Exception $e) {
            $this->logError('Report', 'Failed to get category analysis', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Log errors
     * 
     * @param string $context Error context
     * @param string $message Error message
     * @param array $data Additional data
     */
    private function logError($context, $message, $data = []) {
        if (function_exists('logError')) {
            logError($context, $message, $data);
        } else {
            $logEntry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'context' => $context,
                'message' => $message,
                'data' => $data
            ];
            error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/../logs/report.log');
        }
    }
}
