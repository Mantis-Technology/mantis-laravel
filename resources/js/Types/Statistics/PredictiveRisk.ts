export interface PredictiveRiskAsset {
    asset_id: number;
    code: string;
    type: string | null;
    site: string | null;
    probability: number;
    risk: string;
    risk_color: string;
}

export interface PredictiveRiskSummary {
    assets: number;
    high: number;
    medium: number;
    low: number;
    average_probability: number;
}

export interface PredictiveRisk {
    generated_at: string;
    cutoff: string;
    window_days: number;
    model: string;
    threshold: number;
    summary: PredictiveRiskSummary;
    assets: PredictiveRiskAsset[];
}
