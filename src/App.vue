<template>
  <CallerInfo :ssServer="ssServer" @callerinfo="receptionCallerInfo"></CallerInfo>
  <CallerIsInGroup :ssServer="ssServer" nomgroupe="GoelandManager"
    @calleringroup="receptionCallerInGroupGoelandManager"></CallerIsInGroup>

  <div class="container">
    <!-- Info utilisateur -->
    <div class="user-info">
      <strong>Utilisateur:</strong>
      {{ callerInformation?.prenom }} {{ callerInformation?.nom }}
      ({{ callerInformation?.login }}) - {{ callerInformation?.unite }}
    </div>

    <!-- Message d'erreur -->
    <div v-if="messageErreur" class="message error">
      {{ messageErreur }}
    </div>

    <!-- Formulaire -->
    <div v-if="bGoelandManager" class="form-section">
      <div class="input-group">
        <label>ID Affaire:</label>
        <input type="number" v-model.number="idaffaireInput">
        <button @click="prepareChangement()">Soumettre</button>
      </div>

      <!-- Données affaire -->
      <div v-if="bAffaireData" class="affaire-details">
        <div class="detail-row">
          <strong>Type:</strong> {{ typeAffaire }}
        </div>
        <div class="detail-row">
          <strong>Nom:</strong> {{ nomAffaire }}
        </div>
        <div class="detail-row">
          <strong>Type final:</strong>
          <select v-model="selectedType">
            <option :value="0">-- Sélectionnez un type --</option>
            <option v-for="type in typesFinal" :key="type.value" :value="type">
              {{ type.title }}
            </option>
          </select>
        </div>
      </div>

      <!-- Message de confirmation -->
      <div v-if="bDemandeConfirme" class="confirmation">
        <h3>Confirmation du changement</h3>
        <div class="confirmation-content">
          <div class="detail-row">
            <strong>Affaire:</strong> {{ nomAffaire }} ({{ idaffaire }})
          </div>
          <div class="detail-row">
            <strong>Type actuel:</strong> {{ typeAffaire }}
          </div>
          <div class="detail-row">
            <strong>Type final:</strong> {{ typeFinalTitle }}
          </div>
        </div>
        <div class="confirmation-actions">
          <button class="btn-confirm" @click="changeType()">Confirmer le changement</button>
          <button class="btn-cancel" @click="bDemandeConfirme = false">Annuler</button>
        </div>
      </div>

      <!-- Message d'information -->
      <div v-if="message" class="message success">
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AffaireTypesChange, ApiResponseATC, ApiResponse, TypeAffaire } from '@/axioscalls.ts'
import type { ApiResponseUI, UserInfo } from './components/CallerInfo.vue'
import type { ApiResponseIG } from './components/CallerIsInGroup.vue'
import { getAffaireChangeTypeData, sauveAffaireChangeType } from '@/axioscalls.ts'
import CallerInfo from './components/CallerInfo.vue';
import CallerIsInGroup from './components/CallerIsInGroup.vue';
import { ref, watch } from 'vue'

interface CritereD {
  id: number
}
interface CritereS {
  idaffaire: number
  idtypefinal: number
  idempcaller: number
}

let ssServer: string = ''
if (import.meta.env.DEV) {
  ssServer = 'https://mygolux.lausanne.ch'
}
const ssPageData: string = '/goeland/gestion_spec/affaire_typemodifie/axios/affaire_type_change_data.php'
const ssPageSauve: string = '/goeland/gestion_spec/affaire_typemodifie/axios/affaire_type_change.php'
const callerInformation = ref<UserInfo | null | undefined>(null)
const bGoelandManager = ref<boolean>(false)
const idaffaireInput = ref<string>('')
const idaffaire = ref<number | null>(null)
const typeAffaire = ref<string | null | undefined>('')
const nomAffaire = ref<string | null | undefined>('')
const typesFinal = ref<TypeAffaire[] | undefined>([])
const typeFinalTitle = ref<string>('')
const typeFinalId = ref<number>(0)
const selectedType = ref<TypeAffaire | null>(null)
const messageErreur = ref<string>('')
const bAffaireData = ref<boolean>(false)
const bDemandeConfirme = ref<boolean>(false)
const message = ref<string>('')

const prepareChangement = async () => {
  messageErreur.value = ""
  const valeur = Number(idaffaireInput.value)
  if (idaffaireInput.value === '') {
    messageErreur.value = 'ERREUR: Il faut saisir l\'identifiant de l\'affaire'
  } else if (isNaN(valeur) || !Number.isInteger(valeur)) {
    messageErreur.value = "ERREUR: Veuillez saisir un nombre entier valide"
  } else if (valeur <= 0 || valeur >= 10000000) {
    messageErreur.value = 'ERREUR: Le numéro d\'affaire doit être entre 1 et 9999999'
  } else {
    idaffaire.value = valeur

    const oCritere: CritereD = {
      id: idaffaire.value
    }
    const response: ApiResponseATC = await getAffaireChangeTypeData(ssServer, ssPageData, JSON.stringify(oCritere))
    if (response.success === false) {
      messageErreur.value += `${response.message}\n`
    }
    const returnData: AffaireTypesChange | null = response.success && response.data ? response.data : null
    console.log(returnData)
    typeAffaire.value = returnData?.type
    nomAffaire.value = returnData?.nom
    typesFinal.value = returnData?.typesfinal
    bAffaireData.value = true
    if (typesFinal.value === undefined || typesFinal.value.length === 0) {
      message.value = `Le type d'affaire ${typeAffaire.value} n'a pas de changement de type prévu.\nIl faut indiquer à un développeur goéland : \n Mettre à jour la table [typeaffaire_modification_dico]\ncolonne [idtypeaffaire_initial] : ${returnData?.idtype}\ncolonne [idtypeaffaire_final] : "IdTypeAffaire du type final demandé"\nATTENTION. Si le type d'affaire initial a des données spécialisées, il faut modifier la procédure stockée [cn_affaire_type_modification] pour supprimer la ligne de la table de spécialisation.`
    } else {
      message.value = `Au cas où le type final demandé ne fait pas partie de la liste il faut indiquer à un développeur goéland :\n Mettre à jour la table [typeaffaire_modification_dico]\ncolonne [idtypeaffaire_initial] : ${returnData?.idtype}\ncolonne [idtypeaffaire_final] : "IdTypeAffaire du type final demandé".`
    }
  }
}

watch(selectedType, (newType) => {

  if (newType?.title !== undefined && newType?.value !== 0 && idaffaire.value !== null) {
    typeFinalTitle.value = newType.title
    typeFinalId.value = newType.value
    bDemandeConfirme.value = true
  }
});

const changeType = async () => {
  if (idaffaire.value !== null && callerInformation.value !== undefined && callerInformation.value !== null) {
    const oCritere: CritereS = {
      idaffaire: idaffaire.value,
      idtypefinal: typeFinalId.value,
      idempcaller: callerInformation.value.id
    }

    const response: ApiResponse<[]> = await sauveAffaireChangeType(ssServer, ssPageSauve, JSON.stringify(oCritere))
    if (response.success === false) {
      messageErreur.value += `${response.message}\n`
    }
    else {
      document.location.href = `/goeland/affaire2/affaire_data.php?idaffaire=${idaffaire.value}`
    }
  }
}

const receptionCallerInfo = (jsonData: string) => {
  const retCallerInformation = ref<ApiResponseUI>(JSON.parse(jsonData))
  if (retCallerInformation.value.success) {
    callerInformation.value = retCallerInformation.value.data
  }
}

const receptionCallerInGroupGoelandManager = (jsonData: string) => {
  const retCallerInGroup = ref<ApiResponseIG>(JSON.parse(jsonData))
  if (retCallerInGroup.value.success && retCallerInGroup.value.data !== undefined) {
    bGoelandManager.value = retCallerInGroup.value.data.isingroup
  }
}
</script>

<style scoped>
.container {
  max-width: 800px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.user-info {
  padding: 12px;
  background: #f5f5f5;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.message {
  padding: 12px;
  border-radius: 8px;
  border: 1px solid;
  white-space: pre-line;
}

.message.error {
  background: #fee;
  border-color: #fca;
}

.message.success {
  background: #efe;
  border-color: #afa;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.input-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.input-group input {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  width: 120px;
}

.input-group input[type="number"]::-webkit-inner-spin-button,
.input-group input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.input-group input[type="number"] {
  -moz-appearance: textfield;
}

.input-group button {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.input-group button:hover {
  background: #2563eb;
}

.affaire-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px;
  background: #fafafa;
  border-radius: 8px;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.detail-row select {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  min-width: 250px;
}

.confirmation {
  padding: 20px;
  background: #fff3cd;
  border: 2px solid #ffc107;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.confirmation h3 {
  margin: 0;
  color: #856404;
  font-size: 18px;
}

.confirmation-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px;
  background: white;
  border-radius: 4px;
}

.confirmation-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-confirm,
.btn-cancel {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-confirm {
  background: #28a745;
  color: white;
}

.btn-confirm:hover {
  background: #218838;
}

.btn-cancel {
  background: #6c757d;
  color: white;
}

.btn-cancel:hover {
  background: #5a6268;
}
</style>
