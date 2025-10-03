<template>
  <CallerInfo :ssServer="ssServer" @callerinfo="receptionCallerInfo"></CallerInfo>
  <CallerIsInGroup :ssServer="ssServer" nomgroupe="GoelandManager"
    @calleringroup="receptionCallerInGroupGoelandManager"></CallerIsInGroup>

  <div>
    Utilisateur: {{ callerInformation?.prenom }} {{ callerInformation?.nom }} ({{
      callerInformation?.login }}) - {{ callerInformation?.unite }}
  </div>
  <div v-if="messageErreur !== ''">{{ messageErreur }}</div>
  <div v-if="bGoelandManager === true">
    idaffaire :
    <input type="number" v-model="idaffaireInput"></input>&nbsp;
    <button @click="prepareChangement()">soumet</button>
    <br></br>
    type : {{ typeAffaire }}<br></br>
    nom : {{ nomAffaire }}<br></br>
    type final :
    <select v-model="selectedType">
      <option :value="0">-- Sélectionnez un type --</option>
      <option v-for="type in typesFinal" :key="type.value" :value="type.value">
        {{ type.title }}
      </option>
    </select>
  </div>
  <div v-else>Page réservée au groupe GoélandManager</div>

</template>

<script setup lang="ts">
import type { AffaireTypesChange, ApiResponseATC, TypeAffaire } from '@/axioscalls.ts'
import type { ApiResponseUI, UserInfo } from './components/CallerInfo.vue'
import type { ApiResponseIG } from './components/CallerIsInGroup.vue'
import { getAffaireChangeTypeData } from '@/axioscalls.ts'
import CallerInfo from './components/CallerInfo.vue';
import CallerIsInGroup from './components/CallerIsInGroup.vue';
import { ref } from 'vue'

interface Critere {
  id: number
}

let ssServer: string = ''
if (import.meta.env.DEV) {
  ssServer = 'https://mygolux.lausanne.ch'
}
const ssPage: string = '/goeland/affaire2/axios/affaire_type_change.php'
const callerInformation = ref<UserInfo | null | undefined>(null)
const bGoelandManager = ref<boolean>(false)
const idaffaireInput = ref<string>('')
const idaffaire = ref<number | null>(null)
const typeAffaire = ref<string | null | undefined>('')
const nomAffaire = ref<string | null | undefined>('')
const typesFinal = ref<TypeAffaire[] | undefined>([])
const selectedType = ref<string>('0')
const messageErreur = ref<string>('')


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

    const oCritere: Critere = {
      id: idaffaire.value
    }
    const response: ApiResponseATC = await getAffaireChangeTypeData(ssServer, ssPage, JSON.stringify(oCritere))
    if (response.success === false) {
      messageErreur.value += `${response.message}\n`
    }
    const returnData: AffaireTypesChange | null = response.success && response.data ? response.data : null
    console.log(returnData)
    typeAffaire.value = returnData?.type
    nomAffaire.value = returnData?.nom
    typesFinal.value = returnData?.typesfinal

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

<style scoped></style>
