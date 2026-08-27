// services/rollsApi.js

export class RollsApiService {
   static async fetchByMaterial(materialId) {
      if (!materialId) return [];

      try {
         const response = await fetch(
            `/api/rolls?material_id=${encodeURIComponent(materialId)}`,
            {
               headers: { Accept: 'application/json' },
            }
         );

         if (!response.ok)
            throw new Error(`HTTP error! status: ${response.status}`);
         return await response.json();
      } catch (error) {
         console.error('Ошибка загрузки рулонов:', error);
         return [];
      }
   }
}
