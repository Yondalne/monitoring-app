<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Data;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    // Fonction existante qui simule la réponse de l'API (getData)
        public function getData(Request $request)
        {
            // Obtenir le port de la requête
            $port = $request->getPort();

            // Déterminer l'application en fonction du port
            $application_name = $this->getApplicationNameByPort($port);

            // Générer un timestamp de base (actuel)
            $now = Carbon::now();

            // Générer des données utilisateur aléatoires
            $total_users = rand(100, 110); // nombre total d'utilisateurs
            $active_connections = rand(10, 85); // connexions actives


            // Générer un temps d'utilisation moyen
            $average_usage_time = $this->generateRandomDuration(20, 60); // entre 20 et 60 minutes

            // Générer des durées de connexion aléatoires
            $connection_duration = [
                'average' => $this->generateRandomDuration(30, 60), // durée moyenne entre 30 et 60 minutes
                'min' => $this->generateRandomDuration(5, 10), // minimum 5 à 10 minutes
                'max' => $this->generateRandomDuration(60, 120) // maximum entre 1 et 2 heures
            ];

            // Générer plus d'activités utilisateur aléatoires
            $user_activities = [];
            for ($i = 0; $i < 5; $i++) { // Augmenter le nombre d'activités à 5
                $user_activities[] = [
                    'user_id' => rand(1000, 9999),
                    'action' => $this->getRandomUserAction(),
                    'timestamp' => $now->subMinutes(rand(1, 15))->toIso8601String(),
                ];
            }

            // Réduire le nombre de logs d'erreurs
            $error_logs = [
                [
                    'error_code' => '500',
                    'error_message' => 'Internal Server Error',
                    'occurred_at' => $now->subMinutes(2)->toIso8601String(), // il y a 2 minutes
                ]
            ];

            // Générer des données sur le taux d'erreur
            $total_errors = rand(10, 15); // nombre total d'erreurs réduit
            $error_rate_percentage = round(($total_errors / 10000) * 100, 2); // taux d'erreur en pourcentage

            // Générer la réponse API
            $response = [
                'application_name' => $application_name,
                'timestamp' => Carbon::now()->toIso8601String(),
                'metrics' => [
                    'user_statistics' => [
                        'total_users' => $total_users,
                        'active_connections' => $active_connections,
                        'average_usage_time' => $average_usage_time,
                        'connection_duration' => $connection_duration,
                        'connection_interval' => '00:10:00', // Intervalle de connexion fixe
                    ],
                    'logs' => [
                        'user_activities' => $user_activities,
                        'error_logs' => $error_logs,
                    ],
                    'error_rate' => [
                        'total_errors' => $total_errors,
                        'error_rate_percentage' => $error_rate_percentage,
                    ],
                ]
            ];

            return $response;
        }
    // End Test functions

    // Nouvelle fonction qui convertit les données en format Prometheus
    public function getMetrics(Request $request)
{
    // Appeler getData pour obtenir les données simulées
    $data = $this->getData($request);

    // Créer le format Prometheus
    $app_label = strtolower(str_replace(' ', '_', $data['application_name']));

    // Initialiser les logs des utilisateurs sous forme de compteurs
    $user_logs_metrics = "";

    foreach ($data['metrics']['logs']['user_activities'] as $log) {
        $user_logs_metrics .= "
# HELP {$app_label}_user_action_{$log['action']} Compte des actions utilisateurs pour {$log['action']}
# TYPE {$app_label}_user_action_{$log['action']} counter
{$app_label}_user_action_{$log['action']}{user_id=\"{$log['user_id']}\", action=\"{$log['action']}\"} 1
        ";
    }

    // Initialiser les logs d'erreurs sous forme de compteurs
    $error_logs_metrics = "";

    foreach ($data['metrics']['logs']['error_logs'] as $error) {
        $error_logs_metrics .= "
# HELP {$app_label}_error_{$error['error_code']} Nombre d'occurrences de l'erreur {$error['error_code']}
# TYPE {$app_label}_error_{$error['error_code']} counter
{$app_label}_error_{$error['error_code']}{error_message=\"{$error['error_message']}\", occurred_at=\"{$error['occurred_at']}\"} 1
        ";
    }

    // Formater les autres métriques pour Prometheus
    $formattedMetrics = "
# HELP {$app_label}_total_users Nombre total d'utilisateurs pour {$data['application_name']}
# TYPE {$app_label}_total_users gauge
{$app_label}_total_users{application=\"{$data['application_name']}\"} {$data['metrics']['user_statistics']['total_users']}

# HELP {$app_label}_active_connections Nombre d'utilisateurs actifs connectés pour {$data['application_name']}
# TYPE {$app_label}_active_connections gauge
{$app_label}_active_connections{application=\"{$data['application_name']}\"} {$data['metrics']['user_statistics']['active_connections']}

# HELP {$app_label}_average_usage_time_seconds Temps d'utilisation moyen en secondes pour {$data['application_name']}
# TYPE {$app_label}_average_usage_time_seconds gauge
{$app_label}_average_usage_time_seconds{application=\"{$data['application_name']}\"} {$this->convertToSeconds($data['metrics']['user_statistics']['average_usage_time'])}

# HELP {$app_label}_connection_duration_avg_seconds Durée moyenne des connexions en secondes pour {$data['application_name']}
# TYPE {$app_label}_connection_duration_avg_seconds gauge
{$app_label}_connection_duration_avg_seconds{application=\"{$data['application_name']}\"} {$this->convertToSeconds($data['metrics']['user_statistics']['connection_duration']['average'])}

# HELP {$app_label}_total_errors Nombre total d'erreurs pour {$data['application_name']}
# TYPE {$app_label}_total_errors gauge
{$app_label}_total_errors{application=\"{$data['application_name']}\"} {$data['metrics']['error_rate']['total_errors']}

# HELP {$app_label}_error_rate_percentage Taux d'erreur en pourcentage pour {$data['application_name']}
# TYPE {$app_label}_error_rate_percentage gauge
{$app_label}_error_rate_percentage{application=\"{$data['application_name']}\"} {$data['metrics']['error_rate']['error_rate_percentage']}
    ";

    // Ajouter les logs utilisateurs et logs d'erreurs au format Prometheus
    $formattedMetrics .= $user_logs_metrics . $error_logs_metrics;

    // Retourner la réponse Prometheus au format texte brut
    return response($formattedMetrics, 200)->header('Content-Type', 'text/plain');
}

// Main functions for production
    public function fetchData()
    {
        $apps = Application::all();
        $responses = [];
        foreach ($apps as $app) {
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('GET', $app->url);

                if ($response->getStatusCode() == 200) {
                    $responseData = json_decode($response->getBody(), true);

                    // Insertion dans le modèle Data
                    $data = new Data([
                        'application_id' => $app->id,
                        'data' => json_encode($responseData),
                        'date' => now()
                    ]);
                    $data->save();

                    $responses[$app->id] = [
                        'status' => 'success',
                        'data' => $responseData
                    ];
                } else {
                    $responses[$app->id] = [
                        'status' => 'error',
                        'message' => 'Réponse non valide'
                    ];
                }
            } catch (\Exception $e) {
                $responses[$app->id] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }

        return $responses;
    }
public function fetchMetrics()
{
    // Appeler fetchData pour obtenir les données de toutes les applications
    $appsData = $this->fetchData();

    $allMetrics = '';

    foreach ($appsData as $appId => $appResponse) {
        $app = Application::find($appId);
        $app_label = strtolower(str_replace(' ', '_', $app->name));

        if ($appResponse['status'] === 'success') {
            $data = $appResponse['data'];

            // Formater les métriques pour Prometheus (comme dans votre fonction originale)
            $metrics = $this->formatAppMetrics($app_label, $data);
        } else {
            // En cas d'erreur, créer une métrique d'erreur
            $metrics = "
# HELP {$app_label}_api_error Indique une erreur lors de l'appel à l'API de {$app->name}
# TYPE {$app_label}_api_error gauge
{$app_label}_api_error{application=\"{$app->name}\", error=\"{$appResponse['message']}\"} 1
            ";
        }

        $allMetrics .= $metrics;
    }

    // Retourner la réponse Prometheus au format texte brut
    return response($allMetrics, 200)->header('Content-Type', 'text/plain');
}

private function formatAppMetrics($app_label, $data)
{
    $formattedMetrics = "
# HELP {$app_label}_total_users Nombre total d'utilisateurs
# TYPE {$app_label}_total_users gauge
{$app_label}_total_users{application=\"{$data['application_name']}\"} {$data['metrics']['user_statistics']['total_users']}

# HELP {$app_label}_active_connections Nombre d'utilisateurs actifs connectés
# TYPE {$app_label}_active_connections gauge
{$app_label}_active_connections{application=\"{$data['application_name']}\"} {$data['metrics']['user_statistics']['active_connections']}

# HELP {$app_label}_average_usage_time_seconds Temps d'utilisation moyen en secondes
# TYPE {$app_label}_average_usage_time_seconds gauge
{$app_label}_average_usage_time_seconds{application=\"{$data['application_name']}\"} {$this->convertToSeconds($data['metrics']['user_statistics']['average_usage_time'])}

# HELP {$app_label}_connection_duration_avg_seconds Durée moyenne des connexions en secondes
# TYPE {$app_label}_connection_duration_avg_seconds gauge
{$app_label}_connection_duration_avg_seconds{application=\"{$data['application_name']}\"} {$this->convertToSeconds($data['metrics']['user_statistics']['connection_duration']['average'])}

# HELP {$app_label}_total_errors Nombre total d'erreurs
# TYPE {$app_label}_total_errors gauge
{$app_label}_total_errors{application=\"{$data['application_name']}\"} {$data['metrics']['error_rate']['total_errors']}

# HELP {$app_label}_error_rate_percentage Taux d'erreur en pourcentage
# TYPE {$app_label}_error_rate_percentage gauge
{$app_label}_error_rate_percentage{application=\"{$data['application_name']}\"} {$data['metrics']['error_rate']['error_rate_percentage']}
    ";

    // Ajouter les logs utilisateurs et logs d'erreurs
    $formattedMetrics .= $this->formatUserLogs($app_label, $data['metrics']['logs']['user_activities']);
    $formattedMetrics .= $this->formatErrorLogs($app_label, $data['metrics']['logs']['error_logs']);

    return $formattedMetrics;
}


// End main functions

// Utilities functions
private function formatUserLogs($app_label, $userActivities)
{
    $metrics = '';
    foreach ($userActivities as $log) {
        $metrics .= "
# HELP {$app_label}_user_action_{$log['action']} Compte des actions utilisateurs pour {$log['action']}
# TYPE {$app_label}_user_action_{$log['action']} counter
{$app_label}_user_action_{$log['action']}{user_id=\"{$log['user_id']}\", action=\"{$log['action']}\"} 1
        ";
    }
    return $metrics;
}

private function formatErrorLogs($app_label, $errorLogs)
{
    $metrics = '';
    foreach ($errorLogs as $error) {
        $metrics .= "
# HELP {$app_label}_error_{$error['error_code']} Nombre d'occurrences de l'erreur {$error['error_code']}
# TYPE {$app_label}_error_{$error['error_code']} counter
{$app_label}_error_{$error['error_code']}{error_message=\"{$error['error_message']}\", occurred_at=\"{$error['occurred_at']}\"} 1
        ";
    }
    return $metrics;
}

    // Convertir une durée au format HH:MM:SS en secondes
    private function convertToSeconds($time)
    {
        list($hours, $minutes, $seconds) = explode(':', $time);
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    // Fonction pour déterminer le nom de l'application en fonction du port
    private function getApplicationNameByPort($port)
    {
        switch ($port) {
            case 8000:
                return "GSTOCK";
            case 8001:
                return "CRM";
            case 8002:
                return "Kaydan Express";
            default:
                return "Unknown Application";
        }
    }

    // Fonction pour générer des durées aléatoires
    private function generateRandomDuration($minMinutes, $maxMinutes)
    {
        $minutes = rand($minMinutes, $maxMinutes);
        return sprintf("%02d:%02d:00", floor($minutes / 60), $minutes % 60);
    }

    // Fonction pour obtenir une action utilisateur aléatoire
    private function getRandomUserAction()
    {
        $actions = ['created_order', 'updated_inventory', 'logged_in', 'logged_out', 'viewed_report'];
        return $actions[array_rand($actions)];
    }
}
