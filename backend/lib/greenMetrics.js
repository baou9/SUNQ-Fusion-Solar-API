const CO2_FACTOR_KG_PER_KWH = parseFloat(process.env.CO2_FACTOR_KG_PER_KWH || '0.6');
const TREE_CO2_KG_PER_YEAR = parseFloat(process.env.TREE_CO2_KG_PER_YEAR || '21');
const HOME_KWH_PER_DAY = parseFloat(process.env.HOME_KWH_PER_DAY || '30');

function greenMetrics(kWh) {
  const co2AvoidedKg = kWh * CO2_FACTOR_KG_PER_KWH;
  const treesEquivalent = co2AvoidedKg / TREE_CO2_KG_PER_YEAR;
  const homesPowered = kWh / HOME_KWH_PER_DAY;
  return { co2AvoidedKg, treesEquivalent, homesPowered };
}

module.exports = {
  greenMetrics,
  CO2_FACTOR_KG_PER_KWH,
  TREE_CO2_KG_PER_YEAR,
  HOME_KWH_PER_DAY,
};
