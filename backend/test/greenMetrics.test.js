const { greenMetrics, CO2_FACTOR_KG_PER_KWH, TREE_CO2_KG_PER_YEAR, HOME_KWH_PER_DAY } = require('../lib/greenMetrics');

test('greenMetrics calculates expected values', () => {
  const res = greenMetrics(100);
  expect(res.co2AvoidedKg).toBeCloseTo(100 * CO2_FACTOR_KG_PER_KWH);
  expect(res.treesEquivalent).toBeCloseTo(res.co2AvoidedKg / TREE_CO2_KG_PER_YEAR);
  expect(res.homesPowered).toBeCloseTo(100 / HOME_KWH_PER_DAY);
});
