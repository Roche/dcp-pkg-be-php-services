<?php

namespace Dcp\Enums;

use Dcp\Traits\EnumToArray;

enum DataType: string
{
    use EnumToArray;

    case EQUIPMENT = 'EquipmentData';
    case EXPERIMENT = 'ExperimentData';
    case PROCESS_MONITORING = 'ProcessMonitoringData';
    case EDMS = 'EDMS';
    case ANALYTICAL = 'AnalyticalData';
    case MASTER = 'MasterData';
    case BATCH_GENEALOGY = 'BatchGenealogy';
    case RAW_MATERIALS = 'RawMaterials';
    case ASSET_HIERARCHY = 'AssetHierarchy';
    case EVENT_FRAME_CONTEXT = 'EventframeContext';
    case SENSOR_TELEMETRY = 'SensorTelemetry';
    case ALARMS = 'Alarms';
    case RECIPE_INFORMATION = 'RecipeInformation';
    case SPECTRAL_DATA = 'SpectralData';
    case QUALITY_EVENTS_DATA = 'QualityEventsData';
}
